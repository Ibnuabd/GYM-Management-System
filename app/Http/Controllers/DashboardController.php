<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\PosTransaction;
use App\Models\PtSessionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the main gym management dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Summary statistics (tailored for gym operations)
        $metrics = [
            'total_revenue' => 'Rp 184.500.000',
            'revenue_growth' => '+14.2%',
            'active_members' => 1248,
            'members_growth' => '+8.5%',
            'in_gym_count' => 48,
            'gym_capacity' => 100,
            'completed_pt_sessions' => 384,
            'pt_sessions_growth' => '+18.0%',
            'today_checkins' => 286,
            'pos_sales_today' => 'Rp 5.420.000',
            'pos_transactions_today' => 62,
            'active_pt_quota' => 156,
            'expiring_soon_members' => 18,
        ];

        // Real-time attendance log preview
        $realAttendances = Attendance::with(['member', 'subscription.package'])
            ->today()
            ->latest('scanned_at')
            ->take(10)
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'member_name' => $att->member?->full_name ?? 'Member',
                'member_code' => $att->member?->member_code ?? '-',
                'rfid_uid' => $att->rfid_uid,
                'package_name' => $att->subscription?->package?->name ?? 'Paket Gym Aktif',
                'snapshot_url' => $att->snapshot_url,
                'scanned_time' => $att->scanned_time,
                'time_ago' => $att->time_ago,
                'status' => $att->status,
            ]);

        $recentAttendances = $realAttendances->isNotEmpty() ? $realAttendances->all() : [
            [
                'id' => 1,
                'member_name' => 'Clara Amanda',
                'member_code' => 'MBR-2026-084',
                'rfid_uid' => 'A9F42B',
                'package_name' => 'Gym Regular + PT (12 Sesi)',
                'snapshot_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100&h=100&fit=crop&crop=face',
                'scanned_time' => '17:42:15',
                'time_ago' => '1 menit lalu',
                'status' => 'IN',
            ],
            [
                'id' => 2,
                'member_name' => 'Budi Pratama',
                'member_code' => 'MBR-2026-112',
                'rfid_uid' => 'C3D88E',
                'package_name' => 'VIP All-Access 6 Bln',
                'snapshot_url' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face',
                'scanned_time' => '17:38:02',
                'time_ago' => '5 menit lalu',
                'status' => 'OUT',
            ],
            [
                'id' => 3,
                'member_name' => 'Reza Firmansyah',
                'member_code' => 'MBR-2026-031',
                'rfid_uid' => 'E1174F',
                'package_name' => 'Reguler Bulanan',
                'snapshot_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=100&h=100&fit=crop&crop=face',
                'scanned_time' => '17:25:44',
                'time_ago' => '17 menit lalu',
                'status' => 'IN',
            ],
            [
                'id' => 4,
                'member_name' => 'Siti Rahmawati',
                'member_code' => 'MBR-2026-095',
                'rfid_uid' => '88AF01',
                'package_name' => 'PT Paket 24 Sesi (Sisa 9)',
                'snapshot_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=100&h=100&fit=crop&crop=face',
                'scanned_time' => '17:10:19',
                'time_ago' => '32 menit lalu',
                'status' => 'IN',
            ],
        ];

        $todayCheckinsCount = Attendance::today()->where('status', 'IN')->count();
        if ($todayCheckinsCount > 0) {
            $metrics['today_checkins'] = $todayCheckinsCount;
        }

        $todayPosSales = (float) PosTransaction::today()->completed()->sum('total_amount');
        $todayPosCount = PosTransaction::today()->completed()->count();
        if ($todayPosCount > 0) {
            $metrics['pos_sales_today'] = 'Rp '.number_format($todayPosSales, 0, ',', '.');
            $metrics['pos_transactions_today'] = $todayPosCount;
        }

        // Recent PT session logs (queried live from DB with fallback)
        $realPtSessions = PtSessionLog::with(['member', 'trainer', 'subscription'])
            ->latest('session_date')
            ->take(5)
            ->get()
            ->map(function (PtSessionLog $log) {
                $trainerName = $log->trainer?->name ?? 'Coach Gym';
                $words = explode(' ', $trainerName);
                $initials = count($words) >= 2
                    ? strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1))
                    : strtoupper(substr($trainerName, 0, 2));

                $sub = $log->subscription;
                $remaining = $sub ? max(0, $sub->total_sessions - $sub->used_sessions) : 0;
                $total = $sub?->total_sessions ?? 0;

                return [
                    'id' => $log->id,
                    'member_name' => $log->member?->full_name ?? 'Member',
                    'trainer_name' => $trainerName,
                    'trainer_initials' => $initials,
                    'workout_focus' => $log->muscle_focus,
                    'remaining_sessions' => "{$remaining} / {$total} Sesi",
                    'time_ago' => $log->session_date ? Carbon::parse($log->session_date)->diffForHumans() : '-',
                ];
            });

        $recentPtSessions = $realPtSessions->isNotEmpty() ? $realPtSessions->all() : [
            [
                'id' => 1,
                'member_name' => 'Clara Amanda',
                'trainer_name' => 'Coach Dimas',
                'trainer_initials' => 'DH',
                'workout_focus' => 'Leg Day & Hypertrophy',
                'remaining_sessions' => '7 / 12 Sesi',
                'time_ago' => '12m lalu',
            ],
            [
                'id' => 2,
                'member_name' => 'Hendro Wijaya',
                'trainer_name' => 'Coach Ayu',
                'trainer_initials' => 'AY',
                'workout_focus' => 'Upper Body & Conditioning',
                'remaining_sessions' => '3 / 8 Sesi',
                'time_ago' => '45m lalu',
            ],
            [
                'id' => 3,
                'member_name' => 'Kevin Sanjaya',
                'trainer_name' => 'Coach Dimas',
                'trainer_initials' => 'DH',
                'workout_focus' => 'Core & HIIT Endurance',
                'remaining_sessions' => '11 / 16 Sesi',
                'time_ago' => '1j lalu',
            ],
        ];

        // Hardware kiosk diagnostics
        $hardwareStatus = [
            'rfid_reader' => ['status' => 'Ready', 'type' => 'USB HID Keyboard'],
            'webcam' => ['status' => 'Active', 'type' => '640x480 Canvas API'],
            'pos_printer' => ['status' => 'Online', 'type' => 'ESC/POS Thermal 58mm'],
            'db_latency' => '12 ms',
        ];

        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'recentAttendances' => $recentAttendances,
            'recentPtSessions' => $recentPtSessions,
            'hardwareStatus' => $hardwareStatus,
            'userRole' => method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'staff') : 'staff',
        ]);
    }
}
