<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issue #28: Log Aktivitas, catatan "siapa melakukan apa" untuk semua role staf.
 *
 * Log hanya boleh DITAMBAH, tidak pernah diubah atau dihapus (append-only).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Pelaku. nullOnDelete: log tetap ada walaupun baris user hilang.
            // Null juga dipakai untuk "login gagal" (pelakunya belum tentu user terdaftar).
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Salinan nama & role SAAT aksi terjadi (tidak ikut berubah jika user diedit nanti).
            $table->string('user_name')->nullable();
            $table->string('user_role', 50)->nullable();

            // Kunci aksi singkat, contoh: "member.created", "pos.voided"
            $table->string('action', 100);

            // Kalimat untuk dibaca manusia, contoh: "Menambahkan member Budi Santoso (MBR-0012)"
            $table->string('description', 500);

            // Data yang diubah (opsional), contoh: Member #12
            $table->nullableMorphs('subject');

            // Data tambahan, contoh: {"old": {...}, "new": {...}}
            $table->json('properties')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            // Hanya created_at (log tidak pernah di-update).
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
