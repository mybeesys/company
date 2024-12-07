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
        Schema::create('emp_employee_establishments_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('emp_employees')->nullOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained('est_establishments')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->timestamps();
            $table->unique(['establishment_id', 'employee_id', 'role_id'], 'est_emp_role_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_employee_establishments_roles');
    }
};
