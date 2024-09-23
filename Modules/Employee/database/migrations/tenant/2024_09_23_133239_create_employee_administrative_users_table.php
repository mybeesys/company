<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_administrative_users', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee_employees')->cascadeOnDelete();
            $table->string('userName', 50);
            $table->string('password');
            $table->boolean('accountLocked')->default(false);
            $table->primary(['user_id', 'employee_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administrative_users');
    }
};
