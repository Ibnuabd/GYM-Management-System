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
        Schema::create('pt_session_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_subscription_id')->constrained('member_subscriptions')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('pt_user_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('session_date');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->string('muscle_focus', 150);
            $table->text('workout_notes');
            $table->text('member_feedback')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pt_session_logs');
    }
};
