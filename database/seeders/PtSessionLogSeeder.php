<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PtSessionLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PtSessionLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed sessions for Aditya Pratama (MBR-202610-0001)
        $aditya = Member::where('member_code', 'MBR-202610-0001')->first();
        if ($aditya) {
            $ptSub = MemberSubscription::where('member_id', $aditya->id)
                ->where('total_sessions', '>', 0)
                ->first();

            if ($ptSub && $ptSub->pt_user_id) {
                $logs = [
                    [
                        'member_subscription_id' => $ptSub->id,
                        'member_id' => $aditya->id,
                        'pt_user_id' => $ptSub->pt_user_id,
                        'session_date' => Carbon::now()->subDays(7)->setTime(16, 30),
                        'duration_minutes' => 60,
                        'muscle_focus' => 'Dada & Triceps (Chest & Triceps)',
                        'workout_notes' => "• Barbell Bench Press: 4 set x 10 rep (60 kg)\n• Incline Dumbbell Press: 3 set x 12 rep (18 kg)\n• Cable Chest Fly: 3 set x 15 rep (15 kg)\n• Tricep Rope Pushdown: 4 set x 12 rep (20 kg)",
                        'member_feedback' => 'Postur bench press sangat stabil. Pemanasan rotasi rotator cuff dilakukan dengan baik.',
                    ],
                    [
                        'member_subscription_id' => $ptSub->id,
                        'member_id' => $aditya->id,
                        'pt_user_id' => $ptSub->pt_user_id,
                        'session_date' => Carbon::now()->subDays(4)->setTime(17, 0),
                        'duration_minutes' => 60,
                        'muscle_focus' => 'Punggung & Biceps (Back & Biceps)',
                        'workout_notes' => "• Lat Pulldown Wide-Grip: 4 set x 12 rep (55 kg)\n• Seated Cable Rows: 3 set x 10 rep (50 kg)\n• Dumbbell Single Arm Row: 3 set x 10 rep (22 kg)\n• Ez-Bar Bicep Curls: 3 set x 12 rep (25 kg)",
                        'member_feedback' => 'Koneksi pikiran-otot pada otot lats terasa maksimal. Kontrol tarikan negatif sangat rapi.',
                    ],
                    [
                        'member_subscription_id' => $ptSub->id,
                        'member_id' => $aditya->id,
                        'pt_user_id' => $ptSub->pt_user_id,
                        'session_date' => Carbon::now()->subDays(1)->setTime(18, 15),
                        'duration_minutes' => 75,
                        'muscle_focus' => 'Kaki & Penguatan Inti (Legs & Core)',
                        'workout_notes' => "• Barbell Back Squat: 4 set x 10 rep (75 kg)\n• 45° Leg Press: 3 set x 12 rep (140 kg)\n• Romanian Deadlift: 3 set x 10 rep (60 kg)\n• Hanging Leg Raise: 3 set x 15 rep",
                        'member_feedback' => 'Mobilitas pergelangan kaki sangat memuaskan sehingga squat mencapai kedalaman parallel.',
                    ],
                ];

                foreach ($logs as $logData) {
                    PtSessionLog::updateOrCreate(
                        [
                            'member_subscription_id' => $logData['member_subscription_id'],
                            'session_date' => $logData['session_date'],
                        ],
                        $logData
                    );
                }
            }
        }

        // 2. Seed sessions for Clarissa Stephanie (MBR-202610-0002) if subscription exists
        $clarissa = Member::where('member_code', 'MBR-202610-0002')->first();
        if ($clarissa) {
            $ptSub = MemberSubscription::where('member_id', $clarissa->id)
                ->where('total_sessions', '>', 0)
                ->first();

            if ($ptSub && $ptSub->pt_user_id) {
                PtSessionLog::updateOrCreate(
                    [
                        'member_subscription_id' => $ptSub->id,
                        'session_date' => Carbon::now()->subDays(2)->setTime(10, 0),
                    ],
                    [
                        'member_id' => $clarissa->id,
                        'pt_user_id' => $ptSub->pt_user_id,
                        'duration_minutes' => 60,
                        'muscle_focus' => 'Kebugaran Fungsional & Glutes Activation',
                        'workout_notes' => "• Hip Thrusts: 4 set x 12 rep (40 kg)\n• Goblet Squat: 3 set x 12 rep (16 kg)\n• Battle Rope HIIT: 5 putaran x 30 detik",
                        'member_feedback' => 'Stamina kardio meningkat signifikan. Lanjutkan program hidrasi dan pola tidur minimal 7 jam.',
                    ]
                );
            }
        }
    }
}
