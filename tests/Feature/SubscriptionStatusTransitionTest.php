<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Memastikan tombol "Aktifkan" dan "Batalkan" langganan hanya bekerja
 * untuk status yang diizinkan:
 * - Aktifkan: hanya "queued".
 * - Batalkan: hanya "active" atau "queued".
 */
class SubscriptionStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();
    }

    public function test_queued_subscription_can_be_activated_starting_today(): void
    {
        $package = Package::factory()->create(['duration_days' => 30]);
        $subscription = $this->createSubscription('queued', $package);

        $response = $this->actingAs($this->cs)->post(route('subscriptions.activate', $subscription));

        $response->assertRedirect(route('members.show', $subscription->member_id));
        $response->assertSessionHas('success');

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(Carbon::today()->toDateString(), $subscription->start_date->toDateString());
        $this->assertEquals(Carbon::today()->addDays(29)->toDateString(), $subscription->end_date->toDateString());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statusesThatCannotBeActivated(): array
    {
        return [
            'active' => ['active'],
            'expired' => ['expired'],
            'exhausted' => ['exhausted'],
            'cancelled' => ['cancelled'],
        ];
    }

    #[DataProvider('statusesThatCannotBeActivated')]
    public function test_only_queued_subscription_can_be_activated(string $status): void
    {
        $subscription = $this->createSubscription($status);
        $originalEndDate = $subscription->end_date->toDateString();

        $response = $this->actingAs($this->cs)->post(route('subscriptions.activate', $subscription));

        $response->assertRedirect(route('members.show', $subscription->member_id));
        $response->assertSessionHas('error');

        // Status dan tanggal tidak berubah (tidak ada perpanjangan gratis).
        $subscription->refresh();
        $this->assertEquals($status, $subscription->status);
        $this->assertEquals($originalEndDate, $subscription->end_date->toDateString());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statusesThatCanBeCancelled(): array
    {
        return [
            'active' => ['active'],
            'queued' => ['queued'],
        ];
    }

    #[DataProvider('statusesThatCanBeCancelled')]
    public function test_active_or_queued_subscription_can_be_cancelled(string $status): void
    {
        $subscription = $this->createSubscription($status);

        $response = $this->actingAs($this->cs)->post(route('subscriptions.cancel', $subscription));

        $response->assertSessionHas('success');
        $this->assertEquals('cancelled', $subscription->fresh()->status);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function statusesThatCannotBeCancelled(): array
    {
        return [
            'expired' => ['expired'],
            'exhausted' => ['exhausted'],
            'cancelled' => ['cancelled'],
        ];
    }

    #[DataProvider('statusesThatCannotBeCancelled')]
    public function test_finished_subscription_cannot_be_cancelled(string $status): void
    {
        $subscription = $this->createSubscription($status);

        $response = $this->actingAs($this->cs)->post(route('subscriptions.cancel', $subscription));

        $response->assertSessionHas('error');
        $this->assertEquals($status, $subscription->fresh()->status);
    }

    /**
     * Buat langganan dengan status tertentu.
     */
    private function createSubscription(string $status, ?Package $package = null): MemberSubscription
    {
        return MemberSubscription::factory()->create([
            'member_id' => Member::factory()->create()->id,
            'package_id' => ($package ?? Package::factory()->create())->id,
            'status' => $status,
            'start_date' => Carbon::today()->subDays(40)->toDateString(),
            'end_date' => Carbon::today()->subDays(10)->toDateString(),
        ]);
    }
}
