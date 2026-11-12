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
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('pt_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('used_sessions')->default(0);
            $table->decimal('price_paid', 12, 2)->default(0);
            $table->enum('status', ['active', 'queued', 'expired', 'exhausted', 'cancelled'])->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
    }
};
