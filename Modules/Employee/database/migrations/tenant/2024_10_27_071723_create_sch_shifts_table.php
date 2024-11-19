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
        Schema::create('sch_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('sch_schedules')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('emp_employees')->nullOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->dateTime('startTime')->nullable();
            $table->dateTime('endTime')->nullable();
            $table->integer('break_duration')->nullable();
            $table->foreignId('over_time_rule_id')->nullable()->constrained('sch_time_sheet_rules')->nullOnDelete();
            $table->foreignId('break_rule_id')->nullable()->constrained('sch_time_sheet_rules')->nullOnDelete();
            $table->foreignId('clock_in_out_rule_id')->nullable()->constrained('sch_time_sheet_rules')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
