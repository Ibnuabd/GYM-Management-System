<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePtSessionRequest;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PtSessionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PersonalTrainerController extends Controller
{
    /**
     * Display the Personal Trainer dashboard with assigned members and activity logs.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdminOrSupervisor = $this->isManager($user);

        // Determine trainer scoping
        $selectedTrainerId = null;
        if (! $isAdminOrSupervisor) {
            $selectedTrainerId = $user->id;
        } elseif ($request->filled('trainer_id')) {
            $selectedTrainerId = (int) $request->input('trainer_id');
        }

        // 1. Fetch Assigned Subscriptions
        $subscriptionsQuery = MemberSubscription::with(['member', 'package', 'personalTrainer'])
            ->where('total_sessions', '>', 0);

        if ($selectedTrainerId !== null) {
            $subscriptionsQuery->where('pt_user_id', $selectedTrainerId);
        }

        // Pencarian opsional berdasarkan nama, kode member, atau nomor HP.
        // Catatan: nama kolom nomor HP di tabel "members" adalah "phone" (bukan "phone_number").
        if ($request->filled('search')) {
            $search = $request->input('search');
            $subscriptionsQuery->whereHas('member', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter (active, expired, exhausted, all)
        $statusFilter = $request->input('status', 'active');
        if ($statusFilter === 'active') {
            $subscriptionsQuery->where('status', 'active');
        } elseif ($statusFilter === 'exhausted') {
            $subscriptionsQuery->where(function ($q) {
                $q->where('status', 'exhausted')
                    ->orWhereRaw('used_sessions >= total_sessions');
            });
        } elseif ($statusFilter === 'expired') {
            $subscriptionsQuery->where(function ($q) {
                $q->where('status', 'expired')
                    ->orWhere('end_date', '<', Carbon::today()->toDateString());
            });
        }

        $subscriptions = $subscriptionsQuery->latest('updated_at')->get()->map(function (MemberSubscription $sub) {
            $isExpired = $this->isExpired($sub);
            $isExhausted = $sub->used_sessions >= $sub->total_sessions;
            $canDeduct = ($sub->status === 'active') && (! $isExpired) && (! $isExhausted);

            return [
                'id' => $sub->id,
                'member_id' => $sub->member_id,
                'member_name' => $sub->member?->full_name ?? 'Member',
                'member_code' => $sub->member?->member_code ?? '-',
                'phone' => $sub->member?->phone ?? '-',
                'photo_url' => $sub->member?->photo_asset_url,
                'package_name' => $sub->package?->name ?? 'Paket Personal Trainer',
                'trainer_id' => $sub->pt_user_id,
                'trainer_name' => $sub->personalTrainer?->name ?? 'Belum Ditugaskan',
                'start_date' => $sub->start_date ? Carbon::parse($sub->start_date)->translatedFormat('d M Y') : '-',
                'end_date' => $sub->end_date ? Carbon::parse($sub->end_date)->translatedFormat('d M Y') : '-',
                'total_sessions' => (int) $sub->total_sessions,
                'used_sessions' => (int) $sub->used_sessions,
                'remaining_sessions' => max(0, (int) $sub->total_sessions - (int) $sub->used_sessions),
                'remaining_days' => $sub->remaining_days,
                'progress_percent' => $sub->total_sessions > 0 ? (int) round(($sub->used_sessions / $sub->total_sessions) * 100) : 0,
                'status' => $sub->status,
                'is_expired' => $isExpired,
                'is_exhausted' => $isExhausted,
                'can_deduct' => $canDeduct,
            ];
        });

        // 2. PT Summary Metrics
        $metricTrainerId = $selectedTrainerId ?? $user->id;

        $activeAssignedSubQuery = MemberSubscription::where('status', 'active')
            ->where('total_sessions', '>', 0);
        if ($selectedTrainerId !== null) {
            $activeAssignedSubQuery->where('pt_user_id', $metricTrainerId);
        }

        $assignedMembersCount = (clone $activeAssignedSubQuery)->distinct('member_id')->count('member_id');
        $activeRemainingSessions = (clone $activeAssignedSubQuery)->get()->sum(fn ($s) => max(0, $s->total_sessions - $s->used_sessions));

        $todayConductedQuery = PtSessionLog::whereDate('session_date', Carbon::today());
        if ($selectedTrainerId !== null) {
            $todayConductedQuery->where('pt_user_id', $metricTrainerId);
        }
        $sessionsToday = $todayConductedQuery->count();

        $monthConductedQuery = PtSessionLog::whereMonth('session_date', Carbon::now()->month)
            ->whereYear('session_date', Carbon::now()->year);
        if ($selectedTrainerId !== null) {
            $monthConductedQuery->where('pt_user_id', $metricTrainerId);
        }
        $sessionsThisMonth = $monthConductedQuery->count();

        $metrics = [
            'assigned_members_count' => $assignedMembersCount,
            'active_remaining_sessions' => $activeRemainingSessions,
            'sessions_today' => $sessionsToday,
            'sessions_this_month' => $sessionsThisMonth,
        ];

        // 3. Historical PT Session Logs
        $logsQuery = PtSessionLog::with(['member', 'subscription.package', 'trainer'])
            ->latest('session_date');

        if ($selectedTrainerId !== null) {
            $logsQuery->where('pt_user_id', $selectedTrainerId);
        }

        if ($request->filled('log_search')) {
            $logSearch = $request->input('log_search');
            $logsQuery->where(function ($q) use ($logSearch) {
                $q->whereHas('member', function ($mq) use ($logSearch) {
                    $mq->where('full_name', 'like', "%{$logSearch}%")
                        ->orWhere('member_code', 'like', "%{$logSearch}%");
                })->orWhere('muscle_focus', 'like', "%{$logSearch}%")
                    ->orWhere('workout_notes', 'like', "%{$logSearch}%");
            });
        }

        $historicalLogs = $logsQuery->take(25)->get()->map(function (PtSessionLog $log) {
            return [
                'id' => $log->id,
                'member_id' => $log->member_id,
                'member_name' => $log->member?->full_name ?? 'Member',
                'member_code' => $log->member?->member_code ?? '-',
                'member_photo' => $log->member?->photo_asset_url,
                'trainer_name' => $log->trainer?->name ?? 'Coach Gym',
                'package_name' => $log->subscription?->package?->name ?? 'Paket PT',
                'session_date_formatted' => $log->session_date ? Carbon::parse($log->session_date)->translatedFormat('d M Y, H:i') : '-',
                'time_ago' => $log->session_date ? Carbon::parse($log->session_date)->diffForHumans() : '-',
                'duration_minutes' => $log->duration_minutes,
                'muscle_focus' => $log->muscle_focus,
                'workout_notes' => $log->workout_notes,
                'member_feedback' => $log->member_feedback,
            ];
        });

        // 4. Trainers list for Admin/Supervisor selector
        $trainers = $isAdminOrSupervisor
            ? User::role('pt')->get(['id', 'name', 'email'])
            : [];

        return Inertia::render('Pt/Dashboard', [
            'subscriptions' => $subscriptions,
            'historicalLogs' => $historicalLogs,
            'metrics' => $metrics,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $statusFilter,
                'trainer_id' => $selectedTrainerId,
                'log_search' => $request->input('log_search', ''),
            ],
            'trainers' => $trainers,
            'isManager' => $isAdminOrSupervisor,
            'currentTrainer' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * Potong 1 sesi PT secara atomik dan simpan catatan aktivitas latihan.
     *
     * Alur sederhananya:
     * 1. Data form divalidasi oleh StorePtSessionRequest (fokus otot & catatan wajib diisi).
     * 2. Semua proses dijalankan di dalam DB::transaction(). Jika ada satu langkah gagal,
     *    semua perubahan dibatalkan (rollback), sehingga kuota tidak terpotong tanpa catatan.
     * 3. lockForUpdate() mengunci baris langganan selama transaksi berjalan. Jika dua request
     *    datang bersamaan (misal tombol diklik dua kali), request kedua harus menunggu
     *    request pertama selesai, sehingga kuota tidak terpotong dobel.
     */
    public function storeSession(StorePtSessionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($validated, $user) {
            // Ambil langganan sekaligus KUNCI barisnya sampai transaksi selesai.
            /** @var MemberSubscription $subscription */
            $subscription = MemberSubscription::query()
                ->whereKey($validated['member_subscription_id'])
                ->lockForUpdate()
                ->firstOrFail();

            // Cek 1: PT hanya boleh memotong sesi member yang ditugaskan kepadanya.
            $isAssignedTrainer = (int) $subscription->pt_user_id === (int) $user->id;
            if (! $this->isManager($user) && ! $isAssignedTrainer) {
                abort(403, 'Anda tidak ditugaskan sebagai Personal Trainer untuk langganan member ini.');
            }

            // Cek 2: status paket harus "active".
            if ($subscription->status !== 'active') {
                throw ValidationException::withMessages([
                    'member_subscription_id' => 'Status paket langganan tidak aktif (Status: '.$subscription->status.').',
                ]);
            }

            // Cek 3: masa berlaku paket belum habis.
            if ($this->isExpired($subscription)) {
                throw ValidationException::withMessages([
                    'member_subscription_id' => 'Masa berlaku paket langganan telah berakhir.',
                ]);
            }

            // Cek 4: masih ada sisa sesi (used_sessions < total_sessions).
            if ($subscription->used_sessions >= $subscription->total_sessions) {
                throw ValidationException::withMessages([
                    'member_subscription_id' => 'Kuota sesi PT telah habis (0 sesi tersisa).',
                ]);
            }

            // Semua cek lolos: tambah 1 sesi terpakai.
            $subscription->used_sessions = $subscription->used_sessions + 1;

            // Jika ini sesi terakhir, tandai paket sebagai "exhausted" (kuota habis).
            if ($subscription->used_sessions >= $subscription->total_sessions) {
                $subscription->status = 'exhausted';
            }

            $subscription->save();

            // Simpan catatan aktivitas latihan untuk sesi ini.
            PtSessionLog::create([
                'member_subscription_id' => $subscription->id,
                'member_id' => $subscription->member_id,
                'pt_user_id' => $user->id,
                'session_date' => $validated['session_date'] ?? Carbon::now(),
                'duration_minutes' => $validated['duration_minutes'],
                'muscle_focus' => $validated['muscle_focus'],
                'workout_notes' => $validated['workout_notes'],
                'member_feedback' => $validated['member_feedback'] ?? null,
            ]);
        });

        return back()->with('success', 'Sesi latihan berhasil dicatat dan 1 kuota sesi telah dipotong.');
    }

    /**
     * Retrieve all chronological PT session logs for a specific member.
     */
    public function memberHistory(Request $request, Member $member): JsonResponse
    {
        $user = $request->user();
        $isAdminOrSupervisor = $this->isManager($user);

        $logsQuery = PtSessionLog::with(['trainer', 'subscription.package'])
            ->where('member_id', $member->id)
            ->latest('session_date');

        if (! $isAdminOrSupervisor) {
            $logsQuery->where('pt_user_id', $user->id);
        }

        $logs = $logsQuery->get()->map(function (PtSessionLog $log) {
            return [
                'id' => $log->id,
                'trainer_name' => $log->trainer?->name ?? 'Coach Gym',
                'package_name' => $log->subscription?->package?->name ?? 'Paket PT',
                'session_date_formatted' => $log->session_date ? Carbon::parse($log->session_date)->translatedFormat('d M Y, H:i') : '-',
                'duration_minutes' => $log->duration_minutes,
                'muscle_focus' => $log->muscle_focus,
                'workout_notes' => $log->workout_notes,
                'member_feedback' => $log->member_feedback,
            ];
        });

        return response()->json([
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'code' => $member->member_code,
            ],
            'logs' => $logs,
        ]);
    }

    /**
     * Admin dan supervisor dianggap "manager": boleh melihat & mencatat sesi semua trainer.
     */
    private function isManager(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }

    /**
     * Paket dianggap kedaluwarsa jika tanggal berakhirnya sudah lewat (hari terakhir masih boleh dipakai).
     */
    private function isExpired(MemberSubscription $subscription): bool
    {
        if ($subscription->end_date === null) {
            return false;
        }

        return Carbon::parse($subscription->end_date)->endOfDay()->isPast();
    }
}
