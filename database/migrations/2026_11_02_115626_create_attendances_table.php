<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('rfid_uid', 64)->index();
            $table->enum('status', ['IN', 'OUT'])->default('IN');
            $table->string('snapshot_path')->nullable();
            $table->foreignId('member_subscription_id')->nullable()->constrained('member_subscriptions')->nullOnDelete();
            $table->timestamp('scanned_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
