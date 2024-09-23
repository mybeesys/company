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
        Schema::create('employee_employee_establishments', function (Blueprint $table) {
            $table->foreignId('employee_id')->constrained('employee_employees')->cascadeOnDelete();
            $table->foreignId('establishment_id')->constrained('establishment_establishments')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('wage_id')->constrained('employee_wages')->cascadeOnDelete();
            $table->primary(['establishment_id', 'employee_id', 'role_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_establishments');
    }
};
