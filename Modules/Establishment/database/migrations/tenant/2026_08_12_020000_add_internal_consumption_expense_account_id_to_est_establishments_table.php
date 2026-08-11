<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** MySQL identifier limit is 64; default FK name exceeds it. */
    private const FK_NAME = 'est_internal_consumption_exp_fk';

    public function up(): void
    {
        if (! Schema::hasColumn('est_establishments', 'internal_consumption_expense_account_id')) {
            Schema::table('est_establishments', function (Blueprint $table) {
                $table->unsignedBigInteger('internal_consumption_expense_account_id')
                    ->nullable()
                    ->after('perpetual_inventory_account_id');
            });
        }

        if (! $this->foreignKeyExists(self::FK_NAME)) {
            Schema::table('est_establishments', function (Blueprint $table) {
                $table->foreign('internal_consumption_expense_account_id', self::FK_NAME)
                    ->references('id')
                    ->on('accounting_accounts')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('est_establishments', 'internal_consumption_expense_account_id')) {
            return;
        }

        Schema::table('est_establishments', function (Blueprint $table) {
            if ($this->foreignKeyExists(self::FK_NAME)) {
                $table->dropForeign(self::FK_NAME);
            }
            $table->dropColumn('internal_consumption_expense_account_id');
        });
    }

    private function foreignKeyExists(string $name): bool
    {
        $database = DB::getDatabaseName();
        $count = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $database)
            ->where('TABLE_NAME', 'est_establishments')
            ->where('CONSTRAINT_NAME', $name)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->count();

        return $count > 0;
    }
};
