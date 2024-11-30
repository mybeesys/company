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
        Schema::table('sch_payrolls', function (Blueprint $table) {
            $table->dropColumn('tips');
            $table->dropColumn('gross_wage');
            $table->dropColumn('net_wage');
            $table->decimal('regular_worked_hours', 10, 2)->after('payroll_group_id')->default(0);
            $table->decimal('overtime_hours', 10, 2)->after('regular_worked_hours')->default(0);
            $table->decimal('total_hours', 10, 2)->after('overtime_hours')->default(0);
            $table->integer('total_worked_days')->after('total_hours')->default(0);
            $table->decimal('basic_total_wage', 10, 2)->after('total_worked_days')->default(0);
            $table->decimal('wage_due_before_tax', 10, 2)->after('basic_total_wage')->default(0);
            $table->decimal('total_wage_before_tax', 10, 2)->after('allowances')->default(0);
            $table->decimal('total_wage', 10, 2)->after('total_wage_before_tax')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sch_payrolls', function (Blueprint $table) {
            $table->decimal('tips', 10, 2)->after('taxes_withheld')->default(0);
            $table->decimal('gross_wage', 10, 2)->after('tips')->default(0);
            $table->decimal('net_wage', 10, 2)->after('gross_wage')->default(0);

            $table->dropColumn('regular_worked_hours');
            $table->dropColumn('overtime_hours');
            $table->dropColumn('total_hours');
            $table->dropColumn('total_worked_days');
            $table->dropColumn('basic_total_wage');
            $table->dropColumn('wage_due_before_tax');
            $table->dropColumn('total_wage_before_tax');
            $table->dropColumn('total_wage');
        });

    }
};
