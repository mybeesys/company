<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Employee\Support\DashboardHubPermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @return list<array{name: string, name_ar: string}>
     */
    private function permissions(): array
    {
        return [
            [
                'name' => DashboardHubPermissions::DASHBOARD_SHOW,
                'name_ar' => 'لوحة التحكم',
            ],
            [
                'name' => 'employees.my_companies.show',
                'name_ar' => 'شركاتي',
            ],
            [
                'name' => 'employees.referrals.show',
                'name_ar' => 'شارك واربح',
            ],
            [
                'name' => 'employees.referrals.create',
                'name_ar' => 'شارك واربح',
            ],
        ];
    }

    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');
        $rolePivot = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $rolesTable = config('permission.table_names.roles', 'roles');

        $ids = [];
        foreach ($this->permissions() as $row) {
            $existing = DB::table($table)
                ->where('name', $row['name'])
                ->where('guard_name', 'web')
                ->first();

            if ($existing) {
                $ids[$row['name']] = (int) $existing->id;
                DB::table($table)->where('id', $existing->id)->update([
                    'name_ar' => $row['name_ar'],
                    'updated_at' => now(),
                ]);

                continue;
            }

            $ids[$row['name']] = (int) DB::table($table)->insertGetId([
                'name' => $row['name'],
                'guard_name' => 'web',
                'type' => 'ems',
                'name_ar' => $row['name_ar'],
                'description' => '',
                'description_ar' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table($rolesTable)->pluck('id');
        foreach ($ids as $permissionId) {
            foreach ($roleIds as $roleId) {
                $exists = DB::table($rolePivot)
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();
                if (! $exists) {
                    DB::table($rolePivot)->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        $modelPivot = config('permission.table_names.model_has_permissions', 'model_has_permissions');
        $holders = DB::table($modelPivot)->select('model_type', 'model_id')->distinct()->get();
        foreach ($ids as $permissionId) {
            foreach ($holders as $holder) {
                $exists = DB::table($modelPivot)
                    ->where('permission_id', $permissionId)
                    ->where('model_type', $holder->model_type)
                    ->where('model_id', $holder->model_id)
                    ->exists();
                if (! $exists) {
                    DB::table($modelPivot)->insert([
                        'permission_id' => $permissionId,
                        'model_type' => $holder->model_type,
                        'model_id' => $holder->model_id,
                    ]);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->where('guard_name', 'web')
            ->where('type', 'ems')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
