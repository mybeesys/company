<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Screen\Support\ScreenPermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameLabel('الصفحة الرئيسية');
    }

    public function down(): void
    {
        $this->renameLabel('الشاشات');
    }

    private function renameLabel(string $nameAr): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->whereIn('name', [
                ScreenPermissions::MAIN_SHOW,
                ScreenPermissions::MAIN_CREATE,
                ScreenPermissions::MAIN_UPDATE,
                ScreenPermissions::MAIN_DELETE,
            ])
            ->where('guard_name', 'web')
            ->update([
                'name_ar' => $nameAr,
                'updated_at' => now(),
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
