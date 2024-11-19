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
        Schema::create('emp_employee_est_roles_wages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('emp_employees')->nullOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained('establishment_establishments')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('wage_type', 50)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unique(['establishment_id', 'employee_id', 'role_id'], 'est_emp_role_unique');
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
