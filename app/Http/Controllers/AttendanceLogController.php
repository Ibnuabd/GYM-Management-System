<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceLogFilterRequest;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman "Log Kehadiran & Foto": riwayat scan kartu RFID beserta foto webcam.
 *
 * Hanya untuk admin & supervisor (permission "view_reports").
 */
class AttendanceLogController extends Controller
{
    /**
     * Jumlah baris per halaman tabel.
     */
    private const PER_PAGE = 25;

    /**
     * Tampilkan riwayat absensi sesuai filter.
     */
    public function index(AttendanceLogFilterRequest $request): Response
    {
        $filters = $request->filters();

        // 1. Query dasar: rentang tanggal + pencarian (dipakai untuk tabel DAN ringkasan)
        $baseQuery = Attendance::query()
            ->whereBetween('scanned_at', [$filters['start_date'], $filters['end_date']])
            ->when($filters['search'] !== '', fn (Builder $query) => $this->applySearch($query, $filters['search']));

        // 2. Ringkasan periode (tidak terpengaruh filter status, supaya IN & OUT selalu terlihat)
        $summary = [
            'total_checkins' => (clone $baseQuery)->where('status', 'IN')->count(),
            'total_checkouts' => (clone $baseQuery)->where('status', 'OUT')->count(),
            'unique_members' => (clone $baseQuery)->distinct()->count('member_id'),
        ];

        // 3. Data tabel: filter status, terbaru di atas, 25 baris per halaman
        $attendances = (clone $baseQuery)
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->with(['member', 'subscription.package']) // ambil sekaligus agar tidak N+1 query
            ->latest('scanned_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString() // filter tetap terbawa saat pindah halaman
            ->through(fn (Attendance $attendance) => $this->formatRow($attendance));

        return Inertia::render('Attendances/Index', [
            'attendances' => $attendances,
            'summary' => $summary,
            'filters' => [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->toDateString(),
                'status' => $filters['status'] ?? '',
                'search' => $filters['search'],
            ],
            'maxRangeDays' => AttendanceLogFilterRequest::MAX_RANGE_DAYS,
        ]);
    }

    /**
     * Cari berdasarkan RFID di log, atau nama / kode member.
     *
     * @param  Builder<Attendance>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $searchQuery) use ($search) {
            $searchQuery->where('rfid_uid', 'like', "%{$search}%")
                ->orWhereHas('member', fn (Builder $memberQuery) => $memberQuery
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%"));
        });
    }

    /**
     * Data satu baris tabel.
     *
     * @return array<string, mixed>
     */
    private function formatRow(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'scanned_date' => $attendance->scanned_at?->translatedFormat('d M Y'),
            'scanned_time' => $attendance->scanned_at?->format('H:i:s'),
            'time_ago' => $attendance->time_ago,
            'status' => $attendance->status,
            'rfid_uid' => $attendance->rfid_uid,
            'snapshot_url' => $attendance->snapshot_url,
            'member_name' => $attendance->member?->full_name ?? 'Member dihapus',
            'member_code' => $attendance->member?->member_code ?? '-',
            'package_name' => $attendance->subscription?->package?->name ?? '-',
        ];
    }
}
