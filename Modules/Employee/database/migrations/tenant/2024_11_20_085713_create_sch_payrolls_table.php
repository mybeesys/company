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
        Schema::create('sch_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('emp_employees')->nullOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained('est_establishments')->nullOnDelete();  
            $table->foreignId('payroll_group_id')->nullable()->constrained('sch_payroll_groups')->nullOnDelete();
            $table->decimal('regular_worked_hours', 10, 2)->default(0);
            $table->decimal('overtime_hours', 10, 2)->default(0);
            $table->decimal('total_hours', 10, 2)->default(0);
            $table->integer('total_worked_days')->default(0);
            $table->decimal('basic_total_wage', 10, 2)->default(0);
            $table->decimal('wage_due_before_tax', 10, 2)->default(0);
            $table->decimal('taxes_withheld', 10, 2)->default(0);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('total_wage_before_tax', 10, 2)->default(0);
            $table->decimal('total_wage', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
