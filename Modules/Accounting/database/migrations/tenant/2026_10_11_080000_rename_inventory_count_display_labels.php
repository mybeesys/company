<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Support\AccountingPermissions;

return new class extends Migration
{
    /**
     * User-facing labels only:
     * count screen / permissions → جرد المخزون
     * leftover الجرد الدوري account names → جرد المخزون (count adjustment).
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
                ->whereIn('name_ar', ['الجرد المستمر', 'الجرد الدوري'])
                ->update(['name_ar' => 'جرد المخزون']);
        }

        if (Schema::hasTable('accounting_accounts')) {
            DB::table('accounting_accounts')
                ->whereIn('name_ar', ['تسوية جرد مستمر', 'تسوية جرد دوري'])
                ->update([
                    'name_ar' => 'تسوية جرد المخزون',
                    'name_en' => 'Inventory count adjustment',
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
                ->where('name_ar', 'جرد المخزون')
                ->update(['name_ar' => 'الجرد المستمر']);
        }

        if (Schema::hasTable('accounting_accounts')) {
            DB::table('accounting_accounts')
                ->where('name_ar', 'تسوية جرد المخزون')
                ->where('name_en', 'Inventory count adjustment')
                ->update([
                    'name_ar' => 'تسوية جرد مستمر',
                    'name_en' => 'Perpetual inventory adjustment',
                ]);
        }
    }
};
