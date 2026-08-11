<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('est_establishments', function (Blueprint $table) {
            if (! Schema::hasColumn('est_establishments', 'internal_consumption_expense_account_id')) {
                $table->foreignId('internal_consumption_expense_account_id')
                    ->nullable()
                    ->after('perpetual_inventory_account_id')
                    ->constrained('accounting_accounts')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('est_establishments', function (Blueprint $table) {
            if (Schema::hasColumn('est_establishments', 'internal_consumption_expense_account_id')) {
                $table->dropForeign(['internal_consumption_expense_account_id']);
                $table->dropColumn('internal_consumption_expense_account_id');
            }
        });
    }
};
