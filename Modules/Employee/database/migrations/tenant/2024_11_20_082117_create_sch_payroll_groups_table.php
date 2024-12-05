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
        Schema::create('sch_payroll_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->nullable()->constrained('establishment_establishments')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('date')->nullable();
            $table->string('state')->default('draft');
            $table->string('payment_status')->default('due');
            $table->decimal('gross_total', 12, 2);
            $table->foreignId('created_by')->nullable()->constrained('emp_employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_groups');
    }
};
