<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('est_payment_account_establishment')) {
            return;
        }

        if (! Schema::hasColumn('est_payment_account_establishment', 'account_id')) {
            Schema::table('est_payment_account_establishment', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('establishment_id');
            });

            try {
                Schema::table('est_payment_account_establishment', function (Blueprint $table) {
                    $table->foreign('account_id', 'est_pay_acc_est_account_fk')
                        ->references('id')
                        ->on('accounting_accounts')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
            }
        }

        $now = now();
        $pivots = DB::table('est_payment_account_establishment')
            ->whereNull('account_id')
            ->get(['id', 'payment_account_id']);

        foreach ($pivots as $pivot) {
            $accountId = DB::table('est_establishment_payment_accounts')
                ->where('id', $pivot->payment_account_id)
                ->value('account_id');

            if (! $accountId) {
                continue;
            }

            DB::table('est_payment_account_establishment')
                ->where('id', $pivot->id)
                ->update([
                    'account_id' => (int) $accountId,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('est_payment_account_establishment')
            || ! Schema::hasColumn('est_payment_account_establishment', 'account_id')) {
            return;
        }

        try {
            Schema::table('est_payment_account_establishment', function (Blueprint $table) {
                $table->dropForeign('est_pay_acc_est_account_fk');
            });
        } catch (\Throwable) {
        }

        Schema::table('est_payment_account_establishment', function (Blueprint $table) {
            $table->dropColumn('account_id');
        });
    }
};
