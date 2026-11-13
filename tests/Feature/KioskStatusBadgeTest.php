<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Kartu status kios di bawah sidebar: berdasarkan scan kartu NYATA, bukan teks statis.
 */
class KioskStatusBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected User $cs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->cs = User::where('email', 'cs@fitcore.com')->first();
    }

    public function test_recent_scan_marks_kiosk_as_active(): void
    {
        $member = Member::factory()->create();
        $this->scan($member, 'IN', now()->subMinutes(5));
        $this->scan($member, 'OUT', now()->subMinutes(2));

        $this->actingAs($this->cs)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('kioskStatus.is_active', true)
                ->where('kioskStatus.today_checkins', 1)
                ->where('kioskStatus.last_scan_time', now()->subMinutes(2)->format('H:i'))
            );
    }

    public function test_kiosk_is_inactive_when_last_scan_is_older_than_30_minutes(): void
    {
        $member = Member::factory()->create();
        $scannedAt = Carbon::now()->subMinutes(45);

        // Lewati tes jika 45 menit lalu masih "kemarin" (tes dijalankan tepat setelah tengah malam).
        if (! $scannedAt->isToday()) {
            $this->markTestSkipped('Tes berjalan dekat tengah malam.');
        }

        $this->scan($member, 'IN', $scannedAt);

        $this->actingAs($this->cs)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('kioskStatus.is_active', false)
                ->where('kioskStatus.today_checkins', 1)
            );
    }

    public function test_no_scan_today_shows_empty_status(): void
    {
        $member = Member::factory()->create();
        $this->scan($member, 'IN', Carbon::yesterday()->setTime(10, 0));

        $this->actingAs($this->cs)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('kioskStatus.is_active', false)
                ->where('kioskStatus.last_scan_time', null)
                ->where('kioskStatus.last_scan_ago', null)
                ->where('kioskStatus.today_checkins', 0)
            );
    }

    public function test_personal_trainer_does_not_get_kiosk_status(): void
    {
        $pt = User::where('email', 'pt@fitcore.com')->first();

        $this->actingAs($pt)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('kioskStatus', null));
    }

    private function scan(Member $member, string $status, Carbon $scannedAt): Attendance
    {
        return Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => $member->rfid_uid ?? 'TEST-CARD',
            'status' => $status,
            'scanned_at' => $scannedAt,
        ]);
    }
}
