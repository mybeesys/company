<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_payment_method_fees')) {
            return;
        }

        Schema::table('est_payment_method_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('est_payment_method_fees', 'calculation_method')) {
                $table->string('calculation_method', 2)->default('0')->after('application_type');
            }
            if (! Schema::hasColumn('est_payment_method_fees', 'taxable')) {
                $table->boolean('taxable')->default(false)->after('calculation_method');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_payment_method_fees')) {
            return;
        }

        Schema::table('est_payment_method_fees', function (Blueprint $table) {
            if (Schema::hasColumn('est_payment_method_fees', 'taxable')) {
                $table->dropColumn('taxable');
            }
            if (Schema::hasColumn('est_payment_method_fees', 'calculation_method')) {
                $table->dropColumn('calculation_method');
            }
        });
    }
};
