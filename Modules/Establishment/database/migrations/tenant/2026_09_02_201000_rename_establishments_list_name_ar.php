<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Support\EstablishmentPermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->where('name', EstablishmentPermissions::ESTABLISHMENTS_SHOW)
            ->where('guard_name', 'web')
            ->update([
                'name_ar' => 'الأفرع',
                'updated_at' => now(),
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->where('name', EstablishmentPermissions::ESTABLISHMENTS_SHOW)
            ->where('guard_name', 'web')
            ->update([
                'name_ar' => 'فرع',
                'updated_at' => now(),
            ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
