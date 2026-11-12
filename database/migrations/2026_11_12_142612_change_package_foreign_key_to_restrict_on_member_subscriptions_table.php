<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelumnya: menghapus paket ikut MENGHAPUS semua langganan yang memakai paket itu (cascade).
 * Sekarang: database MENOLAK penghapusan paket selama masih ada langganan yang memakainya (restrict).
 *
 * Ini pengaman kedua. Pengaman pertama ada di PackageController::destroy(),
 * yang hanya menonaktifkan paket yang punya riwayat langganan.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            // Foreign key lama harus dihapus dulu, baru dibuat ulang dengan aturan baru.
            $table->dropForeign(['package_id']);
            $table->foreign('package_id')->references('id')->on('packages')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->foreign('package_id')->references('id')->on('packages')->cascadeOnDelete();
        });
    }
};
