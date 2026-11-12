<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PtSessionLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemberPortalController extends Controller
{
    /**
     * Display the member self-service portal dashboard.
     */
    public function index(Request $request): Response
    {
        /** @var Member $member */
        $member = $request->user('member');

        $member->load([
            'subscriptions.package',
            'subscriptions.personalTrainer',
            'ptSessionLogs.trainer',
            'attendances' => fn ($query) => $query->latest('scanned_at')->take(10),
        ]);

        $activeSubscriptions = $member->subscriptions->filter(function (MemberSubscription $sub) {
            return $sub->status === 'active' && $sub->end_date >= Carbon::today();
        });

        // 1. Digital Membership Card Payload
        $maxEndDate = $activeSubscriptions->max('end_date');
        $expiryCarbon = $maxEndDate ? Carbon::parse($maxEndDate) : null;
        $countdownDays = $expiryCarbon ? max(0, Carbon::today()->diffInDays($expiryCarbon, false)) : 0;

        $digitalCard = [
            'member_code' => $member->member_code,
            'full_name' => $member->full_name,
            'email' => $member->email,
            'phone' => $member->phone,
            'rfid_uid' => $member->rfid_uid ?? 'NOT_ASSIGNED',
            'photo_asset_url' => $member->photo_asset_url,
            'gender' => $member->gender,
            'status' => $member->status,
            'is_active' => $member->status === 'active' && $activeSubscriptions->isNotEmpty(),
            'active_packages' => $activeSubscriptions->pluck('package.name')->filter()->values(),
            'expiry_date' => $expiryCarbon?->toDateString(),
            'expiry_formatted' => $expiryCarbon?->translatedFormat('d M Y') ?? 'Tidak Ada Paket Aktif',
            'countdown_days' => $countdownDays,
        ];

        // 2. Personal Trainer Package Progress
        $ptSubscriptions = $member->subscriptions
            ->filter(fn (MemberSubscription $sub) => $sub->total_sessions > 0)
            ->map(function (MemberSubscription $sub) {
                $used = (int) $sub->used_sessions;
                $total = (int) $sub->total_sessions;
                $remaining = max(0, $total - $used);
                $percent = $total > 0 ? (int) round(($used / $total) * 100) : 0;
                $endCarbon = $sub->end_date ? Carbon::parse($sub->end_date) : null;
                $remainingDays = $endCarbon ? max(0, Carbon::today()->diffInDays($endCarbon, false)) : 0;

                $trainerName = $sub->personalTrainer?->name ?? 'Belum Ditugaskan';
                $words = explode(' ', $trainerName);
                $initials = count($words) >= 2
                    ? strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1))
                    : strtoupper(substr($trainerName, 0, 2));

                return [
                    'id' => $sub->id,
                    'package_name' => $sub->package?->name ?? 'Paket Personal Trainer',
                    'trainer_name' => $trainerName,
                    'trainer_initials' => $initials,
                    'total_sessions' => $total,
                    'used_sessions' => $used,
                    'remaining_sessions' => $remaining,
                    'progress_percent' => $percent,
                    'start_date' => Carbon::parse($sub->start_date)->translatedFormat('d M Y'),
                    'end_date' => $endCarbon?->translatedFormat('d M Y'),
                    'remaining_days' => $remainingDays,
                    'status' => $sub->status,
                    'is_exhausted' => $remaining === 0,
                    'is_expired' => $endCarbon && $endCarbon < Carbon::today(),
                ];
            })
            ->values();

        // 3. Chronological Workout History from PT Session Logs
        $workoutHistory = $member->ptSessionLogs->map(function (PtSessionLog $log) {
            $trainerName = $log->trainer?->name ?? 'Personal Trainer';
            $words = explode(' ', $trainerName);
            $initials = count($words) >= 2
                ? strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1))
                : strtoupper(substr($trainerName, 0, 2));

            return [
                'id' => $log->id,
                'session_date' => $log->session_date->toIso8601String(),
                'date_formatted' => $log->session_date->translatedFormat('d M Y, H:i'),
                'time_ago' => $log->session_date->diffForHumans(),
                'duration_minutes' => $log->duration_minutes,
                'muscle_focus' => $log->muscle_focus,
                'workout_notes' => $log->workout_notes,
                'member_feedback' => $log->member_feedback,
                'trainer_name' => $trainerName,
                'trainer_initials' => $initials,
            ];
        })->values();

        // 4. Gate Access Attendance History
        $recentAttendances = $member->attendances->map(function ($att) {
            return [
                'id' => $att->id,
                'scanned_at' => $att->scanned_at?->translatedFormat('d M Y, H:i:s'),
                'scanned_time' => $att->scanned_time,
                'time_ago' => $att->time_ago,
                'status' => $att->status,
            ];
        })->values();

        return Inertia::render('Portal/Dashboard', [
            'member' => [
                'id' => $member->id,
                'member_code' => $member->member_code,
                'full_name' => $member->full_name,
                'email' => $member->email,
                'phone' => $member->phone,
                'photo_asset_url' => $member->photo_asset_url,
                'status' => $member->status,
            ],
            'digitalCard' => $digitalCard,
            'ptSubscriptions' => $ptSubscriptions,
            'workoutHistory' => $workoutHistory,
            'recentAttendances' => $recentAttendances,
        ]);
    }

    /**
     * Provide JSON API for digital membership card.
     */
    public function digitalCard(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user('member');

        $activeSubscriptions = $member->activeSubscriptions()->with('package')->get();
        $maxEndDate = $activeSubscriptions->max('end_date');
        $expiryCarbon = $maxEndDate ? Carbon::parse($maxEndDate) : null;

        return response()->json([
            'member_code' => $member->member_code,
            'full_name' => $member->full_name,
            'rfid_uid' => $member->rfid_uid,
            'photo_asset_url' => $member->photo_asset_url,
            'status' => $member->status,
            'is_active' => $member->status === 'active' && $activeSubscriptions->isNotEmpty(),
            'active_packages' => $activeSubscriptions->pluck('package.name'),
            'expiry_date' => $expiryCarbon?->toDateString(),
            'countdown_days' => $expiryCarbon ? max(0, Carbon::today()->diffInDays($expiryCarbon, false)) : 0,
        ]);
    }

    /**
     * Provide JSON API for PT package progress tracking.
     */
    public function ptProgress(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user('member');

        $ptSubs = $member->subscriptions()
            ->where('total_sessions', '>', 0)
            ->with(['package', 'personalTrainer'])
            ->get();

        return response()->json($ptSubs);
    }

    /**
     * Provide JSON API for chronological workout history.
     */
    public function workoutHistory(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->user('member');

        $logs = $member->ptSessionLogs()
            ->with('trainer')
            ->get();

        return response()->json($logs);
    }
}
