<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Regular Gym (1 Bulan)',
                'type' => 'gym',
                'duration_days' => 30,
                'session_count' => 0,
                'price' => 350000,
                'description' => 'Akses penuh seluruh alat gym, loker, dan shower selama 30 hari.',
                'is_active' => true,
            ],
            [
                'name' => 'Regular Gym (3 Bulan)',
                'type' => 'gym',
                'duration_days' => 90,
                'session_count' => 0,
                'price' => 950000,
                'description' => 'Paket hemat akses gym reguler 3 bulan bebas jam berkunjung.',
                'is_active' => true,
            ],
            [
                'name' => 'Regular Gym (1 Tahun)',
                'type' => 'gym',
                'duration_days' => 365,
                'session_count' => 0,
                'price' => 3200000,
                'description' => 'Keanggotaan tahunan dengan bonus gratis merchandise & handuk eksklusif.',
                'is_active' => true,
            ],
            [
                'name' => 'Personal Trainer 12 Sesi',
                'type' => 'pt',
                'duration_days' => 45,
                'session_count' => 12,
                'price' => 1800000,
                'description' => '12 sesi latihan intensif 1-on-1 bersama instruktur bersertifikasi (masa aktif 45 hari).',
                'is_active' => true,
            ],
            [
                'name' => 'Personal Trainer 24 Sesi',
                'type' => 'pt',
                'duration_days' => 90,
                'session_count' => 24,
                'price' => 3200000,
                'description' => '24 sesi pendampingan program pembentukan tubuh dan meal plan nutrisi.',
                'is_active' => true,
            ],
            [
                'name' => 'Ultimate Combo (Gym 1 Bln + PT 8 Sesi)',
                'type' => 'combo',
                'duration_days' => 30,
                'session_count' => 8,
                'price' => 1450000,
                'description' => 'Kombinasi fleksibel akses gym reguler 1 bulan plus 8 sesi pendampingan personal trainer.',
                'is_active' => true,
            ],
            [
                'name' => 'VIP Elite Combo (Gym 3 Bln + PT 16 Sesi)',
                'type' => 'combo',
                'duration_days' => 90,
                'session_count' => 16,
                'price' => 2900000,
                'description' => 'Akses gym 3 bulan ditambah 16 sesi PT eksklusif untuk akselerasi target fitness.',
                'is_active' => true,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['name' => $pkg['name']],
                $pkg
            );
        }
    }
}
