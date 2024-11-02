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
        Schema::create('schedule_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('employee_employees')->nullOnDelete();
            $table->foreignId('payroll_period_id')->nullable()->constrained('schedule_payroll_periods')->nullOnDelete();
            $table->decimal('gross_wages', 10, 2)->nullable();
            $table->decimal('net_wages', 10, 2)->nullable();
            $table->decimal('taxes_with_held', 10, 2)->nullable();
            $table->decimal('deductions', 10, 2)->nullable();
            $table->decimal('tips', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
