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
        Schema::create('emp_employees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable();
            $table->string('name_en', 50)->nullable();
            $table->string('user_name', 50)->nullable();
            $table->string('password')->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('PIN', 10)->nullable();
            $table->date('employment_start_date')->nullable();
            $table->date('employment_end_date')->nullable();
            $table->string('image')->nullable();
            $table->boolean('ems_access')->default(false);
            $table->boolean('pos_is_active')->default(true);
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_employees');
    }
};
