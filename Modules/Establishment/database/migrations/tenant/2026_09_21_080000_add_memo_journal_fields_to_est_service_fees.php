<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_establishment_service_fees')) {
            return;
        }

        Schema::table('est_establishment_service_fees', function (Blueprint $table) {
            $after = Schema::hasColumn('est_establishment_service_fees', 'credit_accounting_account_id')
                ? 'credit_accounting_account_id'
                : (Schema::hasColumn('est_establishment_service_fees', 'cashier_payment_method_id')
                    ? 'cashier_payment_method_id'
                    : null);

            if (! Schema::hasColumn('est_establishment_service_fees', 'fee_direction')) {
                $col = $table->string('fee_direction', 20)->nullable();
                if ($after) {
                    $col->after($after);
                }
                $after = 'fee_direction';
            }
            if (! Schema::hasColumn('est_establishment_service_fees', 'accounting_nature')) {
                $col = $table->string('accounting_nature', 40)->nullable();
                if ($after) {
                    $col->after($after);
                }
                $after = 'accounting_nature';
            }
            if (! Schema::hasColumn('est_establishment_service_fees', 'posting_event')) {
                $col = $table->string('posting_event', 30)->nullable();
                if ($after) {
                    $col->after($after);
                }
                $after = 'posting_event';
            }
            foreach ([
                'revenue_account_id',
                'expense_account_id',
                'liability_account_id',
                'output_vat_account_id',
                'input_vat_account_id',
                'settlement_account_id',
            ] as $column) {
                if (Schema::hasColumn('est_establishment_service_fees', $column)) {
                    $after = $column;

                    continue;
                }
                $col = $table->unsignedBigInteger($column)->nullable();
                if ($after) {
                    $col->after($after);
                }
                $after = $column;
            }
        });

        // Preserve earlier debit/credit setup: credit → revenue, keep debit unused for AR (resolved from customer).
        if (
            Schema::hasColumn('est_establishment_service_fees', 'credit_accounting_account_id')
            && Schema::hasColumn('est_establishment_service_fees', 'revenue_account_id')
        ) {
            DB::table('est_establishment_service_fees')
                ->whereNotNull('credit_accounting_account_id')
                ->whereNull('revenue_account_id')
                ->update([
                    'revenue_account_id' => DB::raw('credit_accounting_account_id'),
                    'fee_direction' => DB::raw("COALESCE(fee_direction, 'COLLECTED')"),
                    'accounting_nature' => DB::raw("COALESCE(accounting_nature, 'OWN_REVENUE')"),
                    'posting_event' => DB::raw("COALESCE(posting_event, 'INVOICE')"),
                ]);
        }

        DB::table('est_establishment_service_fees')
            ->whereNull('fee_direction')
            ->update([
                'fee_direction' => 'COLLECTED',
                'accounting_nature' => 'OWN_REVENUE',
                'posting_event' => 'INVOICE',
            ]);

        if (Schema::hasTable('accounting_accounts')) {
            $fks = [
                'revenue_account_id' => 'est_sf_revenue_account_fk',
                'expense_account_id' => 'est_sf_expense_account_fk',
                'liability_account_id' => 'est_sf_liability_account_fk',
                'output_vat_account_id' => 'est_sf_output_vat_account_fk',
                'input_vat_account_id' => 'est_sf_input_vat_account_fk',
                'settlement_account_id' => 'est_sf_settlement_account_fk',
            ];
            Schema::table('est_establishment_service_fees', function (Blueprint $table) use ($fks) {
                foreach ($fks as $col => $name) {
                    if (! Schema::hasColumn('est_establishment_service_fees', $col)) {
                        continue;
                    }
                    try {
                        $table->foreign($col, $name)
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

        $fks = [
            'est_sf_revenue_account_fk',
            'est_sf_expense_account_fk',
            'est_sf_liability_account_fk',
            'est_sf_output_vat_account_fk',
            'est_sf_input_vat_account_fk',
            'est_sf_settlement_account_fk',
        ];
        Schema::table('est_establishment_service_fees', function (Blueprint $table) use ($fks) {
            foreach ($fks as $name) {
                try {
                    $table->dropForeign($name);
                } catch (\Throwable) {
                }
            }
        });

        $cols = [
            'fee_direction',
            'accounting_nature',
            'posting_event',
            'revenue_account_id',
            'expense_account_id',
            'liability_account_id',
            'output_vat_account_id',
            'input_vat_account_id',
            'settlement_account_id',
        ];
        Schema::table('est_establishment_service_fees', function (Blueprint $table) use ($cols) {
            $drop = [];
            foreach ($cols as $col) {
                if (Schema::hasColumn('est_establishment_service_fees', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
