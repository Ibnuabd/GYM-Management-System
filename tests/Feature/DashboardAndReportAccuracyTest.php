<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\PtSessionLog;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Issue #23:
 * - Dashboard memakai data asli (tanpa data contoh), dan data keuangan hanya untuk view_reports.
 * - Laporan tidak menghitung langganan yang dibatalkan sebagai pendapatan.
 * - Filter tanggal laporan divalidasi.
 */
class DashboardAndReportAccuracyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::where('email', 'admin@fitcore.com')->first();
        $this->pt = User::where('email', 'pt@fitcore.com')->first();
    }

    // ---------------------------------------------------------------
    // Dashboard
    // ---------------------------------------------------------------

    public function test_empty_database_shows_zeros_and_no_sample_rows(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('metrics.active_members', 0)
                ->where('metrics.today_checkins', 0)
                ->where('metrics.in_gym_count', 0)
                ->where('metrics.completed_pt_sessions', 0)
                ->where('metrics.active_pt_quota', 0)
                ->where('metrics.expiring_soon_members', 0)
                ->where('metrics.total_revenue', 0)
                ->where('demographics.total', 0)
                ->where('recentAttendances', [])
                ->where('recentPtSessions', [])
            );
    }

    public function test_dashboard_metrics_are_calculated_from_database(): void
    {
        $gymPackage = Package::factory()->create(['type' => 'gym', 'session_count' => 0]);
        $ptPackage = Package::factory()->create(['type' => 'pt', 'session_count' => 10]);

        // Member A: paket gym aktif lama + paket PT aktif (sisa 7 sesi)
        $memberA = Member::factory()->create(['status' => 'active', 'gender' => 'male']);
        $this->createSubscription($memberA, $gymPackage, 'active', Carbon::today()->addDays(60));
        $ptSubscription = $this->createSubscription($memberA, $ptPackage, 'active', Carbon::today()->addDays(60), total: 10, used: 3);

        // Member B: paket berakhir 3 hari lagi tanpa perpanjangan -> "segera kedaluwarsa"
        $memberB = Member::factory()->create(['status' => 'active', 'gender' => 'female']);
        $this->createSubscription($memberB, $gymPackage, 'active', Carbon::today()->addDays(3));

        // Member C: paket berakhir 3 hari lagi TAPI sudah punya perpanjangan -> tidak dihitung
        $memberC = Member::factory()->create(['status' => 'active', 'gender' => 'female']);
        $this->createSubscription($memberC, $gymPackage, 'active', Carbon::today()->addDays(3));
        $this->createSubscription($memberC, $gymPackage, 'queued', Carbon::today()->addDays(33));

        // Absensi hari ini: A masuk, B masuk, A keluar -> 1 orang di dalam gym
        $this->createAttendance($memberA, 'IN');
        $this->createAttendance($memberB, 'IN');
        $this->createAttendance($memberA, 'OUT');

        // 1 sesi PT bulan ini
        PtSessionLog::factory()->create([
            'member_subscription_id' => $ptSubscription->id,
            'member_id' => $memberA->id,
            'pt_user_id' => $this->pt->id,
            'session_date' => Carbon::now(),
        ]);

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('metrics.active_members', 3)
                ->where('metrics.today_checkins', 2)
                ->where('metrics.today_checkouts', 1)
                ->where('metrics.in_gym_count', 1)
                ->where('metrics.completed_pt_sessions', 1)
                ->where('metrics.active_pt_quota', 7)
                ->where('metrics.expiring_soon_members', 1)
                ->where('demographics.male', 1)
                ->where('demographics.female', 2)
                ->has('recentAttendances', 3)
                ->has('recentPtSessions', 1)
                // Tren 6 bulan untuk sparkline: elemen terakhir (index 5) = bulan ini
                ->has('monthlyTrends.new_members', 6)
                ->where('monthlyTrends.new_members.5', 3)
                ->where('monthlyTrends.pt_sessions.5', 1)
            );
    }

    public function test_financial_data_is_only_sent_to_users_with_view_reports_permission(): void
    {
        // PT tidak punya view_reports: data keuangan tidak dikirim sama sekali.
        $this->actingAs($this->pt)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFinancials', false)
                ->where('monthlyRevenue', null)
                ->missing('metrics.total_revenue')
                ->missing('metrics.pos_sales_today')
                ->missing('metrics.pos_transactions_today')
            );

        // Admin punya view_reports.
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFinancials', true)
                ->has('monthlyRevenue', 6)
                ->has('metrics.total_revenue')
                ->has('metrics.pos_sales_today')
            );
    }

    public function test_dashboard_revenue_excludes_cancelled_subscriptions(): void
    {
        $package = Package::factory()->create(['type' => 'gym']);
        $member = Member::factory()->create();
        $this->createSubscription($member, $package, 'active', Carbon::today()->addDays(30), pricePaid: 300000);
        $this->createSubscription($member, $package, 'cancelled', Carbon::today()->addDays(30), pricePaid: 500000);

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('metrics.total_revenue', 300000));
    }

    // ---------------------------------------------------------------
    // Laporan
    // ---------------------------------------------------------------

    public function test_report_revenue_excludes_cancelled_subscriptions(): void
    {
        $package = Package::factory()->create(['type' => 'gym']);
        $member = Member::factory()->create();
        $this->createSubscription($member, $package, 'active', Carbon::today()->addDays(30), pricePaid: 300000);
        $this->createSubscription($member, $package, 'cancelled', Carbon::today()->addDays(30), pricePaid: 500000);

        $this->actingAs($this->admin)->get(route('reports'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('revenueSummary.gym_subscriptions', 300000)
                ->has('transactions', 1)
            );
    }

    public function test_report_with_valid_custom_date_range_works(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports', ['start_date' => '2026-01-01', 'end_date' => '2026-03-31']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('filters.interval', 'custom'));
    }

    public function test_report_with_invalid_date_returns_to_reports_page_with_error(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports', ['start_date' => 'bukan-tanggal', 'end_date' => '2026-03-31']))
            ->assertRedirect(route('reports'))
            ->assertSessionHasErrors('start_date');
    }

    public function test_report_end_date_cannot_be_before_start_date(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports', ['start_date' => '2026-03-31', 'end_date' => '2026-01-01']))
            ->assertRedirect(route('reports'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_report_date_range_cannot_be_longer_than_one_year(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports', ['start_date' => '2024-01-01', 'end_date' => '2026-01-01']))
            ->assertRedirect(route('reports'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_excel_export_with_invalid_date_does_not_cause_server_error(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reports.export.excel', ['start_date' => 'abc', 'end_date' => 'xyz']))
            ->assertRedirect(route('reports'))
            ->assertSessionHasErrors(['start_date', 'end_date']);
    }

    /**
     * Buat langganan yang dibuat (created_at) hari ini.
     */
    private function createSubscription(
        Member $member,
        Package $package,
        string $status,
        Carbon $endDate,
        int $total = 0,
        int $used = 0,
        float $pricePaid = 100000,
    ): MemberSubscription {
        return MemberSubscription::factory()->create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'status' => $status,
            'start_date' => Carbon::today()->subDays(10)->toDateString(),
            'end_date' => $endDate->toDateString(),
            'total_sessions' => $total,
            'used_sessions' => $used,
            'price_paid' => $pricePaid,
            'created_at' => Carbon::now(),
        ]);
    }

    /**
     * Catat absensi hari ini (dibuat langsung, karena AttendanceFactory selalu membuat member tambahan).
     */
    private function createAttendance(Member $member, string $status): Attendance
    {
        return Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => $member->rfid_uid ?? 'TEST-CARD',
            'status' => $status,
            'scanned_at' => Carbon::now(),
        ]);
    }
}
