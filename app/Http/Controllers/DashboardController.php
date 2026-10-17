<?php

namespace App\Http\Controllers;

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
        $recentAttendances = [
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

        // Recent PT session logs
        $recentPtSessions = [
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
            'userRole' => $user->getRoleNames()->first() ?? 'staff',
        ]);
    }
}
