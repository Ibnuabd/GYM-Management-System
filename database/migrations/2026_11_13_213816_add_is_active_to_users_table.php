<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #27: akun staf bisa dinonaktifkan (bukan dihapus).
 *
 * Kenapa tidak dihapus? Tabel pos_transactions, pos_void_logs, dan pt_session_logs
 * memakai cascadeOnDelete ke users. Menghapus user = ikut menghapus riwayat
 * transaksi kasir, audit void, dan sesi PT miliknya.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // true = boleh login, false = akun dinonaktifkan admin
            $table->boolean('is_active')->default(true)->after('supervisor_pin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
