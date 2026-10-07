<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Support\AccountingPermissions;

return new class extends Migration
{
    /**
     * Swap user-facing labels only (الجرد الدوري ↔ الجرد المستمر).
     * Permission names and account codes stay unchanged.
     */
    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        if (Schema::hasTable($table)) {
            DB::table($table)
                ->whereIn('name', [
                    AccountingPermissions::PERIODIC_SHOW,
                    AccountingPermissions::PERIODIC_PRINT,
                    AccountingPermissions::PERIODIC_CREATE,
                    AccountingPermissions::PERIODIC_UPDATE,
                ])
                ->where('name_ar', 'الجرد الدوري')
                ->update(['name_ar' => 'الجرد المستمر']);
        }

        if (Schema::hasTable('accounting_accounts')) {
            DB::table('accounting_accounts')
                ->where('name_ar', 'تسوية جرد دوري')
                ->where('name_en', 'Periodic inventory adjustment')
                ->update([
                    'name_ar' => 'تسوية جرد مستمر',
                    'name_en' => 'Perpetual inventory adjustment',
                ]);
        }
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        if (Schema::hasTable($table)) {
            DB::table($table)
                ->whereIn('name', [
                    AccountingPermissions::PERIODIC_SHOW,
                    AccountingPermissions::PERIODIC_PRINT,
                    AccountingPermissions::PERIODIC_CREATE,
                    AccountingPermissions::PERIODIC_UPDATE,
                ])
                ->where('name_ar', 'الجرد المستمر')
                ->update(['name_ar' => 'الجرد الدوري']);
        }

        if (Schema::hasTable('accounting_accounts')) {
            DB::table('accounting_accounts')
                ->where('name_ar', 'تسوية جرد مستمر')
                ->where('name_en', 'Perpetual inventory adjustment')
                ->update([
                    'name_ar' => 'تسوية جرد دوري',
                    'name_en' => 'Periodic inventory adjustment',
                ]);
        }
    }
};
