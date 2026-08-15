<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'service_fee_amount')) {
                $table->decimal('service_fee_amount', 15, 4)->default(0)->after('tax_amount');
            }
            if (! Schema::hasColumn('transactions', 'service_fee_tax')) {
                $table->decimal('service_fee_tax', 15, 4)->default(0)->after('service_fee_amount');
            }
            if (! Schema::hasColumn('transactions', 'service_fees_payload')) {
                $table->json('service_fees_payload')->nullable()->after('service_fee_tax');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'service_fees_payload')) {
                $table->dropColumn('service_fees_payload');
            }
            if (Schema::hasColumn('transactions', 'service_fee_tax')) {
                $table->dropColumn('service_fee_tax');
            }
            if (Schema::hasColumn('transactions', 'service_fee_amount')) {
                $table->dropColumn('service_fee_amount');
            }
        });
    }
};
