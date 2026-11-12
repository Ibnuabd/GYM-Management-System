<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan status langganan diperbarui otomatis sesuai tanggal:
 * queued -> active (sudah mulai) dan active -> expired (sudah berakhir).
 */
class SubscriptionStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_queued_subscription_becomes_active_when_start_date_arrives(): void
    {
        $startsToday = $this->createSubscription('queued', start: Carbon::today(), end: Carbon::today()->addDays(29));
        $startsTomorrow = $this->createSubscription('queued', start: Carbon::tomorrow(), end: Carbon::today()->addDays(30));

        $this->artisan('subscriptions:sync-status')->assertSuccessful();

        $this->assertEquals('active', $startsToday->fresh()->status);
        $this->assertEquals('queued', $startsTomorrow->fresh()->status, 'Belum waktunya mulai, harus tetap queued.');
    }

    public function test_active_subscription_becomes_expired_after_end_date(): void
    {
        $endedYesterday = $this->createSubscription('active', start: Carbon::today()->subDays(30), end: Carbon::yesterday());
        $endsToday = $this->createSubscription('active', start: Carbon::today()->subDays(29), end: Carbon::today());

        $this->artisan('subscriptions:sync-status')->assertSuccessful();

        $this->assertEquals('expired', $endedYesterday->fresh()->status);
        $this->assertEquals('active', $endsToday->fresh()->status, 'Hari terakhir masih boleh dipakai.');
    }

    public function test_other_statuses_are_not_changed(): void
    {
        $cancelled = $this->createSubscription('cancelled', start: Carbon::today()->subDays(30), end: Carbon::yesterday());
        $exhausted = $this->createSubscription('exhausted', start: Carbon::today()->subDays(30), end: Carbon::yesterday());

        $this->artisan('subscriptions:sync-status')->assertSuccessful();

        $this->assertEquals('cancelled', $cancelled->fresh()->status);
        $this->assertEquals('exhausted', $exhausted->fresh()->status);
    }

    public function test_command_is_safe_to_run_multiple_times(): void
    {
        $this->createSubscription('queued', start: Carbon::today(), end: Carbon::today()->addDays(29));
        $this->createSubscription('active', start: Carbon::today()->subDays(30), end: Carbon::yesterday());

        $this->artisan('subscriptions:sync-status')
            ->expectsOutputToContain('(queued -> active): 1')
            ->expectsOutputToContain('(active -> expired): 1')
            ->assertSuccessful();

        // Jalan kedua: semua sudah benar, jadi tidak ada yang berubah.
        $this->artisan('subscriptions:sync-status')
            ->expectsOutputToContain('(queued -> active): 0')
            ->expectsOutputToContain('(active -> expired): 0')
            ->assertSuccessful();
    }

    public function test_command_is_scheduled_daily_at_00_05(): void
    {
        $scheduledEvent = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'subscriptions:sync-status'));

        $this->assertNotNull($scheduledEvent, 'Command belum didaftarkan di routes/console.php.');
        $this->assertEquals('5 0 * * *', $scheduledEvent->expression);
    }

    public function test_kiosk_activates_renewal_that_starts_today_even_if_scheduler_has_not_run(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $cs = User::where('email', 'cs@fitcore.com')->first();

        $member = Member::factory()->create(['rfid_uid' => 'RENEWAL-CARD-01', 'status' => 'active']);

        // Paket lama sudah berakhir kemarin, paket perpanjangan mulai hari ini (masih "queued").
        $this->createSubscription('active', start: Carbon::today()->subDays(30), end: Carbon::yesterday(), member: $member);
        $renewal = $this->createSubscription('queued', start: Carbon::today(), end: Carbon::today()->addDays(29), member: $member);

        $response = $this->actingAs($cs)->postJson(route('kiosk.scan'), ['rfid_uid' => 'RENEWAL-CARD-01']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertEquals('active', $renewal->fresh()->status);
    }

    /**
     * Buat langganan dengan status dan rentang tanggal tertentu.
     */
    private function createSubscription(string $status, Carbon $start, Carbon $end, ?Member $member = null): MemberSubscription
    {
        return MemberSubscription::factory()->create([
            'member_id' => ($member ?? Member::factory()->create())->id,
            'status' => $status,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ]);
    }
}
