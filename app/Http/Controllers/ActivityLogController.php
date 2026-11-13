<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityLogFilterRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Halaman Log Aktivitas: riwayat aksi semua staf (issue #28).
 *
 * Hanya bisa DILIHAT oleh admin (permission "view_activity_logs").
 * Log dicatat di controller lain lewat ActivityLog::record(...).
 */
class ActivityLogController extends Controller
{
    /**
     * Jumlah baris per halaman tabel.
     */
    private const PER_PAGE = 25;

    /**
     * Tampilkan daftar log + filter + ringkasan.
     */
    public function index(ActivityLogFilterRequest $request): Response
    {
        $filters = $request->filters();

        // Langkah 1: ringkasan untuk rentang tanggal yang dipilih.
        $logsInRange = ActivityLog::query()->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

        $summary = [
            'total_activities' => (clone $logsInRange)->count(),
            'active_users' => (clone $logsInRange)->whereNotNull('user_id')->distinct()->count('user_id'),
            'failed_logins' => (clone $logsInRange)->where('action', 'auth.login_failed')->count(),
            'pos_voids' => (clone $logsInRange)->where('action', 'pos.voided')->count(),
        ];

        // Langkah 2: tabel log sesuai SEMUA filter, terbaru di atas.
        $logs = (clone $logsInRange)
            ->when($filters['user_id'], fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['role'], fn (Builder $query, string $role) => $query->where('user_role', $role))
            ->when($filters['area'], fn (Builder $query, string $area) => $query->where('action', 'like', $area.'.%'))
            ->when($filters['search'] !== '', fn (Builder $query) => $query->where('description', 'like', '%'.$filters['search'].'%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn (ActivityLog $log) => $this->formatRow($log));

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'summary' => $summary,
            'filters' => [
                'start_date' => $filters['start_date']->toDateString(),
                'end_date' => $filters['end_date']->toDateString(),
                'user_id' => $filters['user_id'],
                'role' => $filters['role'],
                'area' => $filters['area'],
                'search' => $filters['search'],
            ],
            // Pilihan dropdown filter
            'users' => User::orderBy('name')->get(['id', 'name']),
            'roles' => User::STAFF_ROLES,
            'areas' => ActivityLog::AREAS,
            'maxRangeDays' => ActivityLogFilterRequest::MAX_RANGE_DAYS,
        ]);
    }

    /**
     * Ubah 1 log menjadi data baris tabel.
     *
     * @return array<string, mixed>
     */
    private function formatRow(ActivityLog $log): array
    {
        $area = $log->area();

        return [
            'id' => $log->id,
            'date' => $log->created_at->translatedFormat('d M Y'),
            'time' => $log->created_at->format('H:i:s'),
            'time_ago' => $log->created_at->diffForHumans(),
            'user_name' => $log->user_name ?? 'Tidak dikenal',
            'user_role' => $log->user_role,
            'action' => $log->action,
            'area' => $area,
            'area_label' => ActivityLog::AREAS[$area] ?? $area,
            'description' => $log->description,
            // Contoh: "Member #12". Kosong jika aksi tidak terkait data tertentu (misal login).
            'subject' => $log->subject_type ? class_basename($log->subject_type).' #'.$log->subject_id : null,
            'properties' => $log->properties,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
        ];
    }
}
