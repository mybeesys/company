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
        Schema::table('sch_payroll_groups', function (Blueprint $table) {
            $table->decimal('net_total', 12, 2)->after('gross_total')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_group', function (Blueprint $table) {
            $table->dropColumn('net_total');
        });
    }
};
