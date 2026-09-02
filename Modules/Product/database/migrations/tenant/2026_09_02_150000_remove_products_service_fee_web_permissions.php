<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Service fees belong to establishment cashier settings, not item management.
     *
     * @return list<string>
     */
    private function names(): array
    {
        return [
            'products.service fee.show',
            'products.service fee.create',
            'products.service fee.update',
            'products.service fee.delete',
        ];
    }

    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');
        $rolePivot = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $modelPivot = config('permission.table_names.model_has_permissions', 'model_has_permissions');

        $ids = DB::table($table)
            ->whereIn('name', $this->names())
            ->where('guard_name', 'web')
            ->where('type', 'ems')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table($rolePivot)->whereIn('permission_id', $ids)->delete();
        DB::table($modelPivot)->whereIn('permission_id', $ids)->delete();
        DB::table($table)->whereIn('id', $ids)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        foreach ($this->names() as $name) {
            $exists = DB::table($table)
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table($table)->insert([
                'name' => $name,
                'guard_name' => 'web',
                'type' => 'ems',
                'name_ar' => 'رسوم الخدمات',
                'description' => '',
                'description_ar' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
