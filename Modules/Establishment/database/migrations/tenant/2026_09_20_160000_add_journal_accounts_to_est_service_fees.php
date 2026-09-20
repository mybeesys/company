<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_establishment_service_fees')) {
            return;
        }

        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('est_establishment_service_fees', 'debit_accounting_account_id')) {
                $table->unsignedBigInteger('debit_accounting_account_id')->nullable()->after('cashier_payment_method_id');
            }
            if (! Schema::hasColumn('est_establishment_service_fees', 'credit_accounting_account_id')) {
                $table->unsignedBigInteger('credit_accounting_account_id')->nullable()->after('debit_accounting_account_id');
            }
        });

        if (Schema::hasTable('accounting_accounts')) {
            Schema::table('est_establishment_service_fees', function (Blueprint $table) {
                if (Schema::hasColumn('est_establishment_service_fees', 'debit_accounting_account_id')) {
                    try {
                        $table->foreign('debit_accounting_account_id', 'est_sf_debit_account_fk')
                            ->references('id')
                            ->on('accounting_accounts')
                            ->nullOnDelete();
                    } catch (\Throwable) {
                    }
                }
                if (Schema::hasColumn('est_establishment_service_fees', 'credit_accounting_account_id')) {
                    try {
                        $table->foreign('credit_accounting_account_id', 'est_sf_credit_account_fk')
                            ->references('id')
                            ->on('accounting_accounts')
                            ->nullOnDelete();
                    } catch (\Throwable) {
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_establishment_service_fees')) {
            return;
        }

        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            try {
                $table->dropForeign('est_sf_debit_account_fk');
            } catch (\Throwable) {
            }
            try {
                $table->dropForeign('est_sf_credit_account_fk');
            } catch (\Throwable) {
            }

            $cols = [];
            if (Schema::hasColumn('est_establishment_service_fees', 'debit_accounting_account_id')) {
                $cols[] = 'debit_accounting_account_id';
            }
            if (Schema::hasColumn('est_establishment_service_fees', 'credit_accounting_account_id')) {
                $cols[] = 'credit_accounting_account_id';
            }
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
