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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_code', 50)->unique();
            $table->string('nik', 20)->nullable()->unique();
            $table->string('full_name', 150);
            $table->string('phone', 30)->index();
            $table->string('email', 150)->nullable();
            $table->string('rfid_uid', 100)->nullable()->unique();
            $table->string('photo_url', 255)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->default('male');
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
