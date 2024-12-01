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
        Schema::create('sch_establishment_payroll_groups', function (Blueprint $table) {
            $table->foreignId('establishment_id')->constrained('establishment_establishments')->cascadeOnDelete();
            $table->foreignId('payroll_group_id')->constrained('sch_payroll_groups')->cascadeOnDelete();            
            $table->timestamps();
            $table->primary(['establishment_id', 'payroll_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sch_establishment_payroll_groups');
    }
};
