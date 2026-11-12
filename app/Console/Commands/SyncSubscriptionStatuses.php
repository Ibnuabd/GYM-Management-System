<?php

namespace App\Console\Commands;

use App\Models\MemberSubscription;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Memperbarui status langganan member berdasarkan tanggal hari ini.
 *
 * Dijalankan otomatis setiap hari oleh scheduler (lihat routes/console.php).
 * Bisa juga dijalankan manual:  php artisan subscriptions:sync-status
 *
 * Aman dijalankan berkali-kali: jika tidak ada yang perlu diubah, hasilnya 0.
 */
#[Signature('subscriptions:sync-status')]
#[Description('Aktifkan langganan "queued" yang sudah mulai dan kedaluwarsakan langganan "active" yang sudah berakhir')]
class SyncSubscriptionStatuses extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. queued -> active  (tanggal mulai sudah tiba)
        $activatedCount = MemberSubscription::activateQueuedThatHaveStarted();

        // 2. active -> expired (tanggal berakhir sudah lewat)
        $expiredCount = MemberSubscription::expireActiveThatHaveEnded();

        $this->info("Langganan diaktifkan (queued -> active): {$activatedCount}");
        $this->info("Langganan kedaluwarsa (active -> expired): {$expiredCount}");

        return self::SUCCESS;
    }
}
