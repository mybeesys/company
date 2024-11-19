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
        Schema::create('emp_allowances_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained('emp_employees')->nullOnDelete();
            $table->foreignId('allowance_type_id')->nullable()->constrained('emp_allowance_types')->nullOnDelete();
            $table->string('description')->nullable();
            $table->string('description_en')->nullable();
            $table->enum('type', ['allowance', 'deduction'])->default('allowance');
            $table->decimal('amount', 10, 2);
            $table->enum('amount_type', ['fixed', 'percent'])->default('fixed');
            $table->date('applicable_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_deductions');
    }
};
