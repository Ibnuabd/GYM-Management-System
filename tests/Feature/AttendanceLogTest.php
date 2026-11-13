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
 * Issue #26: halaman Log Kehadiran & Foto (/attendances).
 */
class AttendanceLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $supervisor;

    protected User $cs;

    protected User $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->supervisor = User::where('email', 'supervisor@fitcore.com')->first();
        $this->cs = User::where('email', 'cs@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();
    }

    // ---------------------------------------------------------------
    // Akses
    // ---------------------------------------------------------------

    public function test_admin_and_supervisor_can_open_attendance_log(): void
    {
        $this->actingAs($this->admin)->get(route('attendances.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Attendances/Index'));

        $this->actingAs($this->supervisor)->get(route('attendances.index'))->assertOk();
    }

    public function test_cs_and_pt_cannot_open_attendance_log(): void
    {
        $this->actingAs($this->cs)->get(route('attendances.index'))->assertForbidden();
        $this->actingAs($this->pt)->get(route('attendances.index'))->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Filter
    // ---------------------------------------------------------------

    public function test_default_filter_shows_only_todays_scans_newest_first(): void
    {
        $member = Member::factory()->create(['full_name' => 'Rina Hari Ini']);
        $this->scan($member, 'IN', Carbon::today()->setTime(8, 0));
        $this->scan($member, 'OUT', Carbon::today()->setTime(10, 0));
        $this->scan($member, 'IN', Carbon::yesterday()->setTime(9, 0)); // kemarin: tidak boleh muncul

        $this->actingAs($this->admin)->get(route('attendances.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.start_date', Carbon::today()->toDateString())
                ->where('filters.end_date', Carbon::today()->toDateString())
                ->has('attendances.data', 2)
                ->where('attendances.data.0.status', 'OUT') // terbaru di atas
                ->where('attendances.data.0.member_name', 'Rina Hari Ini')
            );
    }

    public function test_date_range_filter(): void
    {
        $member = Member::factory()->create();
        $this->scan($member, 'IN', Carbon::today()->subDays(3)->setTime(9, 0));
        $this->scan($member, 'IN', Carbon::today()->subDays(10)->setTime(9, 0)); // di luar rentang

        $this->actingAs($this->admin)->get(route('attendances.index', [
            'start_date' => Carbon::today()->subDays(5)->toDateString(),
            'end_date' => Carbon::today()->toDateString(),
        ]))->assertInertia(fn (Assert $page) => $page->has('attendances.data', 1));
    }

    public function test_status_filter(): void
    {
        $member = Member::factory()->create();
        $this->scan($member, 'IN', Carbon::today()->setTime(8, 0));
        $this->scan($member, 'OUT', Carbon::today()->setTime(9, 0));

        $this->actingAs($this->admin)->get(route('attendances.index', ['status' => 'OUT']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('attendances.data', 1)
                ->where('attendances.data.0.status', 'OUT')
            );
    }

    public function test_search_by_member_name_member_code_or_rfid(): void
    {
        $budi = Member::factory()->create(['full_name' => 'Budi Santoso', 'member_code' => 'MBR-CARI-001', 'rfid_uid' => 'RFID-BUDI-77']);
        $sinta = Member::factory()->create(['full_name' => 'Sinta Dewi', 'member_code' => 'MBR-LAIN-002', 'rfid_uid' => 'RFID-SINTA-88']);
        $this->scan($budi, 'IN', Carbon::today()->setTime(10, 0));
        $this->scan($sinta, 'IN', Carbon::today()->setTime(10, 0));

        foreach (['Budi', 'MBR-CARI', 'BUDI-77'] as $keyword) {
            $this->actingAs($this->admin)->get(route('attendances.index', ['search' => $keyword]))
                ->assertInertia(fn (Assert $page) => $page
                    ->has('attendances.data', 1)
                    ->where('attendances.data.0.member_name', 'Budi Santoso')
                );
        }
    }

    public function test_pagination_keeps_filters_in_page_links(): void
    {
        $member = Member::factory()->create();
        for ($minute = 0; $minute < 30; $minute++) {
            $this->scan($member, 'IN', Carbon::today()->setTime(8, $minute));
        }

        $this->actingAs($this->admin)->get(route('attendances.index', ['status' => 'IN']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('attendances.data', 25)
                ->where('attendances.total', 30)
                ->where('attendances.next_page_url', fn ($url) => str_contains($url, 'status=IN') && str_contains($url, 'page=2'))
            );
    }

    public function test_invalid_filters_redirect_back_with_errors(): void
    {
        $this->actingAs($this->admin)->get(route('attendances.index', ['start_date' => 'bukan-tanggal']))
            ->assertRedirect(route('attendances.index'))
            ->assertSessionHasErrors('start_date');

        $this->actingAs($this->admin)->get(route('attendances.index', ['start_date' => '2026-03-31', 'end_date' => '2026-01-01']))
            ->assertRedirect(route('attendances.index'))
            ->assertSessionHasErrors('end_date');

        // Lebih dari 31 hari
        $this->actingAs($this->admin)->get(route('attendances.index', ['start_date' => '2026-01-01', 'end_date' => '2026-03-01']))
            ->assertRedirect(route('attendances.index'))
            ->assertSessionHasErrors('end_date');

        $this->actingAs($this->admin)->get(route('attendances.index', ['status' => 'MASUK']))
            ->assertRedirect(route('attendances.index'))
            ->assertSessionHasErrors('status');
    }

    // ---------------------------------------------------------------
    // Ringkasan & baris tabel
    // ---------------------------------------------------------------

    public function test_summary_counts_checkins_checkouts_and_unique_members(): void
    {
        $memberA = Member::factory()->create();
        $memberB = Member::factory()->create();
        $this->scan($memberA, 'IN', Carbon::today()->setTime(7, 0));
        $this->scan($memberA, 'OUT', Carbon::today()->setTime(8, 0));
        $this->scan($memberB, 'IN', Carbon::today()->setTime(9, 0));

        // Filter status OUT tidak mengubah ringkasan (IN & OUT selalu dihitung)
        $this->actingAs($this->admin)->get(route('attendances.index', ['status' => 'OUT']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.total_checkins', 2)
                ->where('summary.total_checkouts', 1)
                ->where('summary.unique_members', 2)
            );
    }

    public function test_row_without_snapshot_has_null_snapshot_url(): void
    {
        $member = Member::factory()->create();
        $this->scan($member, 'IN', Carbon::today()->setTime(10, 0));

        $this->actingAs($this->admin)->get(route('attendances.index'))
            ->assertInertia(fn (Assert $page) => $page->where('attendances.data.0.snapshot_url', null));
    }

    /**
     * Catat satu scan kartu. Dibuat langsung (bukan AttendanceFactory),
     * karena factory selalu membuat member & langganan tambahan.
     */
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
