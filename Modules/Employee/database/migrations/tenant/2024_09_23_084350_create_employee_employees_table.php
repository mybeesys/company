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
        Schema::create('employee_employees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->nullable();
            $table->string('name_en', 50)->nullable();
            $table->string('email', 100)->unique()->nullable();
            $table->string('phoneNumber', 20)->nullable();
            $table->string('PIN', 10)->nullable();
            $table->date('employmentStartDate')->nullable();
            $table->date('employmentEndDate')->nullable();
            $table->string('image')->nullable();
            $table->decimal('mileageReimbursementRate',5,2)->nullable();
            $table->boolean('isActive')->default(true);
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
