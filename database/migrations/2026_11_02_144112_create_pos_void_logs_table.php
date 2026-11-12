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
        Schema::create('pos_void_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_transaction_id')->constrained('pos_transactions')->cascadeOnDelete();
            $table->string('invoice_number', 64)->index();
            $table->foreignId('cashier_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2);
            $table->json('items_summary');
            $table->text('void_reason');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_void_logs');
    }
};
