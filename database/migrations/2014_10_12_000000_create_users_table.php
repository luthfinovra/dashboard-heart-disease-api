<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Database\Seeders\AdminUserSeeder;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('institution', 100)->nullable();
            $table->enum('gender', ['male', 'female', 'prefer not to say'])->default('prefer not to say')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->text('tujuan_permohonan')->nullable();
            $table->enum('role', ['admin', 'operator', 'researcher'])->default('researcher');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->rememberToken();
            $table->timestamps();
        });

        (new AdminUserSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
