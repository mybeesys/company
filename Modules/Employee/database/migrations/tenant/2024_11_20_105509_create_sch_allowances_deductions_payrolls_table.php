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
        Schema::create('sch_adjustments_payrolls', function (Blueprint $table) {
            $table->foreignId('adjustment_id')->constrained('emp_payroll_adjustments')->cascadeOnDelete();
            $table->foreignId('payroll_id')->constrained('sch_payrolls')->cascadeOnDelete();
            $table->primary(['adjustment_id', 'payroll_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowances_deductions_payrolls');
    }
};
