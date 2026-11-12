<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Package;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ptCoach = User::role('pt')->first();
        $gym1Mo = Package::where('name', 'Regular Gym (1 Bulan)')->first();
        $gym3Mo = Package::where('name', 'Regular Gym (3 Bulan)')->first();
        $pt12 = Package::where('name', 'Personal Trainer 12 Sesi')->first();
        $combo = Package::where('name', 'Ultimate Combo (Gym 1 Bln + PT 8 Sesi)')->first();

        $membersData = [
            [
                'member_code' => 'MBR-202610-0001',
                'nik' => '3201123456780001',
                'full_name' => 'Aditya Pratama',
                'phone' => '081234567890',
                'email' => 'aditya.pratama@gmail.com',
                'rfid_uid' => 'E2-80-11-01',
                'gender' => 'male',
                'birth_date' => '1995-04-12',
                'address' => 'Jl. Kemang Raya No. 14, Jakarta Selatan',
                'status' => 'active',
                'subscriptions' => [
                    [
                        'package' => $gym1Mo,
                        'pt_user_id' => null,
                        'start_date' => Carbon::today()->subDays(10),
                        'status' => 'active',
                        'used_sessions' => 0,
                    ],
                    [
                        'package' => $pt12,
                        'pt_user_id' => $ptCoach?->id,
                        'start_date' => Carbon::today()->subDays(10),
                        'status' => 'active',
                        'used_sessions' => 3,
                    ],
                ],
            ],
            [
                'member_code' => 'MBR-202610-0002',
                'nik' => '3201123456780002',
                'full_name' => 'Clarissa Stephanie',
                'phone' => '081398765432',
                'email' => 'clarissa.steph@gmail.com',
                'rfid_uid' => 'A4-9F-3B-22',
                'gender' => 'female',
                'birth_date' => '1998-08-23',
                'address' => 'Apartemen Senopati Suites Tower 2, Jakarta',
                'status' => 'active',
                'subscriptions' => [
                    [
                        'package' => $combo,
                        'pt_user_id' => $ptCoach?->id,
                        'start_date' => Carbon::today()->subDays(5),
                        'status' => 'active',
                        'used_sessions' => 2,
                    ],
                ],
            ],
            [
                'member_code' => 'MBR-202610-0003',
                'nik' => '3201123456780003',
                'full_name' => 'Reza Rahardian Putra',
                'phone' => '082155667788',
                'email' => 'reza.rahardian@yahoo.com',
                'rfid_uid' => 'C9-12-EA-88',
                'gender' => 'male',
                'birth_date' => '1992-11-05',
                'address' => 'Jl. Tebet Barat Dalam No. 88, Jakarta Selatan',
                'status' => 'active',
                'subscriptions' => [
                    [
                        'package' => $gym3Mo,
                        'pt_user_id' => null,
                        'start_date' => Carbon::today()->subDays(20),
                        'status' => 'active',
                        'used_sessions' => 0,
                    ],
                ],
            ],
            [
                'member_code' => 'MBR-202610-0004',
                'nik' => '3201123456780004',
                'full_name' => 'Dewi Lestari Kusuma',
                'phone' => '085711223344',
                'email' => 'dewi.kusuma@outlook.com',
                'rfid_uid' => 'F1-78-90-AA',
                'gender' => 'female',
                'birth_date' => '1996-02-17',
                'address' => 'Perum Bintaro Jaya Sektor 7, Tangerang Selatan',
                'status' => 'inactive',
                'subscriptions' => [
                    [
                        'package' => $gym1Mo,
                        'pt_user_id' => null,
                        'start_date' => Carbon::today()->subDays(45),
                        'status' => 'expired',
                        'used_sessions' => 0,
                    ],
                ],
            ],
        ];

        foreach ($membersData as $mData) {
            $subsData = $mData['subscriptions'];
            unset($mData['subscriptions']);
            $mData['password'] = 'password';

            $member = Member::updateOrCreate(
                ['member_code' => $mData['member_code']],
                $mData
            );

            foreach ($subsData as $sub) {
                if (! $sub['package']) {
                    continue;
                }

                $package = $sub['package'];
                $startDate = Carbon::parse($sub['start_date']);
                $endDate = $startDate->copy()->addDays($package->duration_days - 1);

                MemberSubscription::updateOrCreate(
                    [
                        'member_id' => $member->id,
                        'package_id' => $package->id,
                        'start_date' => $startDate->toDateString(),
                    ],
                    [
                        'pt_user_id' => $sub['pt_user_id'],
                        'end_date' => $endDate->toDateString(),
                        'total_sessions' => $package->session_count,
                        'used_sessions' => $sub['used_sessions'],
                        'price_paid' => $package->price,
                        'status' => $sub['status'],
                        'notes' => 'Seeded sample subscription',
                    ]
                );
            }
        }
    }
}
