<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\MemberSubscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceKioskController extends Controller
{
    /**
     * Display the dedicated attendance kiosk interface.
     */
    public function index(Request $request): Response
    {
        $recentAttendances = Attendance::with(['member', 'subscription.package'])
            ->today()
            ->latest('scanned_at')
            ->take(20)
            ->get()
            ->map(fn (Attendance $att) => $this->formatAttendancePayload($att));

        $todayInCount = Attendance::today()->where('status', 'IN')->count();
        $todayOutCount = Attendance::today()->where('status', 'OUT')->count();

        return Inertia::render('Kiosk/Index', [
            'recentAttendances' => $recentAttendances,
            'stats' => [
                'today_in_count' => $todayInCount,
                'today_out_count' => $todayOutCount,
                'total_scans_today' => $todayInCount + $todayOutCount,
            ],
        ]);
    }

    /**
     * Process RFID tap and optional camera snapshot from the kiosk.
     */
    public function scan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_uid' => ['required', 'string', 'max:64'],
            'snapshot' => ['nullable', 'string'],
        ]);

        $rfid = trim($validated['rfid_uid']);
        $member = Member::where('rfid_uid', $rfid)->first();

        // 1. Verify Member exists
        if (! $member) {
            return response()->json([
                'success' => false,
                'error_code' => 'UNREGISTERED_CARD',
                'message' => "Kartu RFID '{$rfid}' belum terdaftar dalam sistem.",
                'rfid_uid' => $rfid,
            ], 422);
        }

        // 2. Verify Member is active
        if ($member->status !== 'active') {
            return response()->json([
                'success' => false,
                'error_code' => 'INACTIVE_MEMBER',
                'message' => "Akses Ditolak: Member {$member->full_name} berstatus ".($member->status === 'inactive' ? 'non-aktif' : 'ditangguhkan').'.',
                'member' => [
                    'id' => $member->id,
                    'member_code' => $member->member_code,
                    'full_name' => $member->full_name,
                    'photo_asset_url' => $member->photo_asset_url,
                ],
            ], 422);
        }

        // 3. Verify Active Subscription
        // Cadangan jika scheduler belum berjalan hari ini: paket perpanjangan ("queued")
        // milik member ini yang tanggal mulainya sudah tiba, langsung diaktifkan sekarang.
        MemberSubscription::activateQueuedThatHaveStarted($member->id);

        $activeSub = $member->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', Carbon::today())
            ->with('package')
            ->latest('end_date')
            ->first();

        if (! $activeSub) {
            return response()->json([
                'success' => false,
                'error_code' => 'EXPIRED_SUBSCRIPTION',
                'message' => "Akses Ditolak: Paket langganan member {$member->full_name} telah kedaluwarsa atau belum aktif.",
                'member' => [
                    'id' => $member->id,
                    'member_code' => $member->member_code,
                    'full_name' => $member->full_name,
                    'photo_asset_url' => $member->photo_asset_url,
                    'remaining_validity_days' => 0,
                ],
            ], 422);
        }

        // 4. Toggle Status (IN if first check-in today or last status was OUT; OUT if last status was IN today)
        $todayLastAttendance = Attendance::where('member_id', $member->id)
            ->today()
            ->latest('scanned_at')
            ->first();

        $status = ($todayLastAttendance === null || $todayLastAttendance->status === 'OUT') ? 'IN' : 'OUT';

        // 5. Save Snapshot if provided
        $snapshotPath = null;
        if (! empty($validated['snapshot'])) {
            $snapshotPath = $this->storeSnapshot($member->id, $validated['snapshot']);
        }

        // 6. Record Attendance
        $attendance = Attendance::create([
            'member_id' => $member->id,
            'rfid_uid' => $member->rfid_uid ?? $rfid,
            'status' => $status,
            'snapshot_path' => $snapshotPath,
            'member_subscription_id' => $activeSub->id,
            'scanned_at' => Carbon::now(),
        ]);

        $attendance->setRelation('member', $member);
        $attendance->setRelation('subscription', $activeSub);

        $message = $status === 'IN'
            ? "Selamat datang, {$member->full_name}! Check-in berhasil."
            : "Sampai jumpa kembali, {$member->full_name}! Check-out berhasil.";

        return response()->json([
            'success' => true,
            'message' => $message,
            'attendance' => $this->formatAttendancePayload($attendance),
        ]);
    }

    /**
     * Retrieve recent attendance logs for live reactive updates.
     */
    public function recentLogs(Request $request): JsonResponse
    {
        $recentAttendances = Attendance::with(['member', 'subscription.package'])
            ->today()
            ->latest('scanned_at')
            ->take(20)
            ->get()
            ->map(fn (Attendance $att) => $this->formatAttendancePayload($att));

        $todayInCount = Attendance::today()->where('status', 'IN')->count();
        $todayOutCount = Attendance::today()->where('status', 'OUT')->count();

        return response()->json([
            'recentAttendances' => $recentAttendances,
            'stats' => [
                'today_in_count' => $todayInCount,
                'today_out_count' => $todayOutCount,
                'total_scans_today' => $todayInCount + $todayOutCount,
            ],
        ]);
    }

    /**
     * Decode and store webcam snapshot into public storage structured directory.
     */
    protected function storeSnapshot(int $memberId, string $base64Payload): ?string
    {
        try {
            $extension = 'jpg';
            $data = $base64Payload;

            if (preg_match('/^data:image\/(\w+);base64,/', $base64Payload, $matches)) {
                $matchedExt = strtolower($matches[1]);
                $extension = match ($matchedExt) {
                    'webp' => 'webp',
                    'png' => 'png',
                    default => 'jpg',
                };
                $data = substr($base64Payload, strpos($base64Payload, ',') + 1);
            }

            $decoded = base64_decode($data);
            if ($decoded === false) {
                return null;
            }

            $dateFolder = Carbon::now()->format('Y/m');
            $fileName = sprintf(
                'snap_%d_%s_%s.%s',
                $memberId,
                Carbon::now()->format('Ymd_His'),
                bin2hex(random_bytes(4)),
                $extension
            );

            $storagePath = "attendances/{$dateFolder}/{$fileName}";
            Storage::disk('public')->put($storagePath, $decoded);

            return $storagePath;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Format attendance payload for consistent frontend response.
     *
     * @return array<string, mixed>
     */
    protected function formatAttendancePayload(Attendance $attendance): array
    {
        $member = $attendance->member;
        $sub = $attendance->subscription;
        $packageName = $sub?->package?->name ?? 'Paket Gym Aktif';

        return [
            'id' => $attendance->id,
            'status' => $attendance->status,
            'scanned_time' => $attendance->scanned_time,
            'scanned_at' => $attendance->scanned_at?->toIso8601String(),
            'time_ago' => $attendance->time_ago ?? 'Baru saja',
            'snapshot_url' => $attendance->snapshot_url,
            'member_id' => $attendance->member_id,
            'member_name' => $member?->full_name ?? 'Member',
            'member_code' => $member?->member_code ?? '-',
            'member_photo' => $member?->photo_asset_url,
            'rfid_uid' => $attendance->rfid_uid,
            'package_name' => $packageName,
            'remaining_validity_days' => $member?->remaining_validity_days ?? 0,
            'pt_session_balance' => $member?->pt_session_balance ?? 0,
        ];
    }
}
