<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PosTransaction;
use App\Models\PtSessionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard utama staf.
 *
 * SEMUA angka dihitung dari database (tidak ada data contoh/palsu).
 * Jika database masih kosong, angka akan tampil 0 dan tabel tampil kosong.
 *
 * Data KEUANGAN (pendapatan, penjualan kasir, grafik pendapatan) hanya dikirim
 * ke user yang punya permission "view_reports" (admin & supervisor).
 */
class DashboardController extends Controller
{
    /**
     * Kapasitas maksimal orang di dalam gym (dipakai untuk persentase okupansi).
     */
    private const GYM_CAPACITY = 100;

    /**
     * Member dianggap "segera kedaluwarsa" jika paketnya berakhir dalam X hari ke depan.
     */
    private const EXPIRING_SOON_DAYS = 7;

    /**
     * Display the main dashboard.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $canViewFinancials = $user->can('view_reports');

        $metrics = $this->operationalMetrics();

        $monthlyRevenue = null;
        if ($canViewFinancials) {
            $monthlyRevenue = $this->monthlyRevenue();
            $metrics = [...$metrics, ...$this->financialMetrics($monthlyRevenue)];
        }

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'canViewFinancials' => $canViewFinancials,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyTrends' => $this->monthlyTrends(),
            'demographics' => $this->memberDemographics(),
            'databaseLatencyMs' => $this->measureDatabaseLatencyMs(),
            'recentAttendances' => $this->recentAttendances(),
            'recentPtSessions' => $this->recentPtSessions(),
            'userRole' => $user->getRoleNames()->first() ?? 'staff',
        ]);
    }

    /**
     * Angka operasional yang boleh dilihat semua staf.
     *
     * @return array<string, int>
     */
    private function operationalMetrics(): array
    {
        $today = Carbon::today();

        $todayCheckins = Attendance::today()->where('status', 'IN')->count();
        $todayCheckouts = Attendance::today()->where('status', 'OUT')->count();

        return [
            // Member berstatus aktif yang punya paket aktif
            'active_members' => Member::where('status', 'active')->whereHas('activeSubscriptions')->count(),
            'new_members_this_week' => Member::where('created_at', '>=', now()->subDays(7))->count(),

            // Orang di dalam gym = jumlah masuk - jumlah keluar hari ini
            'in_gym_count' => max(0, $todayCheckins - $todayCheckouts),
            'gym_capacity' => self::GYM_CAPACITY,
            'today_checkins' => $todayCheckins,
            'today_checkouts' => $todayCheckouts,

            // Sesi PT yang diselesaikan bulan ini
            'completed_pt_sessions' => PtSessionLog::whereBetween('session_date', [$today->copy()->startOfMonth(), now()])->count(),

            // Total sisa sesi PT dari semua paket PT yang aktif
            'active_pt_quota' => (int) MemberSubscription::where('status', 'active')
                ->where('total_sessions', '>', 0)
                ->sum(DB::raw('total_sessions - used_sessions')),

            'expiring_soon_members' => $this->countExpiringSoonMembers(),
            'today_void_count' => PosTransaction::today()->voided()->count(),
        ];
    }

    /**
     * Angka keuangan (hanya untuk permission "view_reports").
     *
     * @param  list<array{gym: float, pt: float, pos: float}>  $monthlyRevenue
     * @return array<string, float|int>
     */
    private function financialMetrics(array $monthlyRevenue): array
    {
        // Bulan berjalan = elemen terakhir dari grafik 6 bulan.
        $thisMonth = end($monthlyRevenue);

        return [
            'total_revenue' => $thisMonth['gym'] + $thisMonth['pt'] + $thisMonth['pos'],
            'pos_sales_today' => (float) PosTransaction::today()->completed()->sum('total_amount'),
            'pos_transactions_today' => PosTransaction::today()->completed()->count(),
        ];
    }

    /**
     * Pendapatan per bulan untuk 6 bulan terakhir (termasuk bulan ini).
     *
     * - gym: langganan paket gym
     * - pt : langganan paket PT dan combo
     * - pos: penjualan kasir yang tidak di-VOID
     * Langganan yang dibatalkan tidak dihitung (lihat scope countedAsRevenue).
     *
     * @return list<array{month: string, gym: float, pt: float, pos: float, is_current: bool}>
     */
    private function monthlyRevenue(): array
    {
        $months = [];

        foreach ($this->lastSixMonths() as $month) {
            $period = [$month['start'], $month['end']];

            $months[] = [
                'month' => $month['label'],
                'gym' => (float) MemberSubscription::countedAsRevenue()
                    ->whereBetween('created_at', $period)
                    ->whereHas('package', fn ($query) => $query->where('type', 'gym'))
                    ->sum('price_paid'),
                'pt' => (float) MemberSubscription::countedAsRevenue()
                    ->whereBetween('created_at', $period)
                    ->whereHas('package', fn ($query) => $query->whereIn('type', ['pt', 'combo']))
                    ->sum('price_paid'),
                'pos' => (float) PosTransaction::completed()
                    ->whereBetween('created_at', $period)
                    ->sum('total_amount'),
                'is_current' => $month['is_current'],
            ];
        }

        return $months;
    }

    /**
     * Tren 6 bulan terakhir untuk grafik garis kecil (sparkline) di kartu dashboard.
     * Boleh dilihat semua staf (bukan data keuangan).
     *
     * @return array{new_members: list<int>, pt_sessions: list<int>}
     */
    private function monthlyTrends(): array
    {
        $newMembers = [];
        $ptSessions = [];

        foreach ($this->lastSixMonths() as $month) {
            $period = [$month['start'], $month['end']];

            $newMembers[] = Member::whereBetween('created_at', $period)->count();
            $ptSessions[] = PtSessionLog::whereBetween('session_date', $period)->count();
        }

        return [
            'new_members' => $newMembers,
            'pt_sessions' => $ptSessions,
        ];
    }

    /**
     * Daftar 6 bulan terakhir (5 bulan lalu sampai bulan ini), urut dari yang paling lama.
     *
     * @return list<array{start: Carbon, end: Carbon, label: string, is_current: bool}>
     */
    private function lastSixMonths(): array
    {
        $months = [];

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $monthStart = Carbon::today()->startOfMonth()->subMonthsNoOverflow($monthsAgo);

            $months[] = [
                'start' => $monthStart,
                'end' => $monthStart->copy()->endOfMonth(),
                'label' => $monthStart->translatedFormat('M'),
                'is_current' => $monthsAgo === 0,
            ];
        }

        return $months;
    }

    /**
     * Jumlah member aktif berdasarkan jenis kelamin.
     *
     * @return array{total: int, male: int, female: int, other: int}
     */
    private function memberDemographics(): array
    {
        $countsByGender = Member::where('status', 'active')
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $male = (int) ($countsByGender['male'] ?? 0);
        $female = (int) ($countsByGender['female'] ?? 0);
        $other = (int) ($countsByGender['other'] ?? 0);

        return [
            'total' => $male + $female + $other,
            'male' => $male,
            'female' => $female,
            'other' => $other,
        ];
    }

    /**
     * Member yang paket aktifnya berakhir dalam 7 hari DAN belum punya paket lanjutan.
     */
    private function countExpiringSoonMembers(): int
    {
        $today = Carbon::today();
        $limitDate = $today->copy()->addDays(self::EXPIRING_SOON_DAYS);

        return Member::where('status', 'active')
            // Punya paket aktif yang berakhir antara hari ini dan 7 hari lagi...
            ->whereHas('subscriptions', fn ($query) => $query
                ->where('status', 'active')
                ->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $limitDate))
            // ...dan TIDAK punya paket aktif/antrean yang berakhir setelah itu (sudah perpanjang).
            ->whereDoesntHave('subscriptions', fn ($query) => $query
                ->whereIn('status', ['active', 'queued'])
                ->whereDate('end_date', '>', $limitDate))
            ->count();
    }

    /**
     * Ukur waktu respons database (milidetik) dengan query paling ringan.
     */
    private function measureDatabaseLatencyMs(): float
    {
        $startedAt = microtime(true);
        DB::select('SELECT 1');

        return round((microtime(true) - $startedAt) * 1000, 1);
    }

    /**
     * 10 log absensi terbaru hari ini.
     *
     * @return list<array<string, mixed>>
     */
    private function recentAttendances(): array
    {
        return Attendance::with(['member', 'subscription.package'])
            ->today()
            ->latest('scanned_at')
            ->take(10)
            ->get()
            ->map(fn (Attendance $attendance) => [
                'id' => $attendance->id,
                'member_name' => $attendance->member?->full_name ?? 'Member',
                'member_code' => $attendance->member?->member_code ?? '-',
                'rfid_uid' => $attendance->rfid_uid,
                'package_name' => $attendance->subscription?->package?->name ?? 'Paket Gym Aktif',
                'snapshot_url' => $attendance->snapshot_url,
                'scanned_time' => $attendance->scanned_time,
                'time_ago' => $attendance->time_ago,
                'status' => $attendance->status,
            ])
            ->all();
    }

    /**
     * 5 sesi PT terbaru.
     *
     * @return list<array<string, mixed>>
     */
    private function recentPtSessions(): array
    {
        return PtSessionLog::with(['member', 'trainer', 'subscription'])
            ->latest('session_date')
            ->take(5)
            ->get()
            ->map(function (PtSessionLog $log) {
                $trainerName = $log->trainer?->name ?? 'Coach Gym';
                $subscription = $log->subscription;
                $remaining = $subscription ? max(0, $subscription->total_sessions - $subscription->used_sessions) : 0;
                $total = $subscription?->total_sessions ?? 0;

                return [
                    'id' => $log->id,
                    'member_name' => $log->member?->full_name ?? 'Member',
                    'trainer_name' => $trainerName,
                    'trainer_initials' => $this->initialsOf($trainerName),
                    'workout_focus' => $log->muscle_focus,
                    'remaining_sessions' => "{$remaining} / {$total} Sesi",
                    'time_ago' => $log->session_date?->diffForHumans() ?? '-',
                ];
            })
            ->all();
    }

    /**
     * Inisial dari nama, contoh: "Coach Dimas" -> "CD", "Dimas" -> "DI".
     */
    private function initialsOf(string $name): string
    {
        $words = explode(' ', trim($name));

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1));
        }

        return strtoupper(substr($name, 0, 2));
    }
}
