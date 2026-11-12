<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Jadwal otomatis (scheduler).
| Agar jadwal ini berjalan, server harus menjalankan "php artisan schedule:run" setiap menit
| (lihat README bagian "Scheduler"). Saat development bisa pakai: php artisan schedule:work
*/

// Setiap hari pukul 00:05 (timezone aplikasi, lihat config/app.php):
// aktifkan paket perpanjangan yang sudah mulai & kedaluwarsakan paket yang sudah berakhir.
Schedule::command('subscriptions:sync-status')->dailyAt('00:05');
