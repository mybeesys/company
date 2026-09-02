<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Franchise\Support\FranchisePermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @return list<array{name: string, name_ar: string}>
     */
    private function permissions(): array
    {
        $labels = [
            'Companies' => 'شركات الامتياز التجاري',
            'Branches' => 'إدارة فروع الفرنشايز',
            'Products' => 'إدارة منتجات الفرنشايز',
            'Menus' => 'صلاحيات القوائم المخصصة',
        ];

        $rows = [];
        foreach ($labels as $entity => $nameAr) {
            foreach (FranchisePermissions::crud($entity) as $name) {
                $rows[] = ['name' => $name, 'name_ar' => $nameAr];
            }
        }

        return $rows;
    }

    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');
        $rolePivot = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $modelPivot = config('permission.table_names.model_has_permissions', 'model_has_permissions');

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

        foreach (['show', 'create', 'update', 'delete'] as $action) {
            $source = match ($action) {
                'show' => FranchisePermissions::ALL_SHOW,
                'create' => FranchisePermissions::ALL_CREATE,
                'update' => FranchisePermissions::ALL_UPDATE,
                'delete' => FranchisePermissions::ALL_DELETE,
            };
            $targets = [];
            foreach (FranchisePermissions::tabEntities() as $entity) {
                $crud = FranchisePermissions::crud($entity);
                if (isset($crud[$action], $ids[$crud[$action]])) {
                    $targets[] = $ids[$crud[$action]];
                }
            }
            $this->copyAssignments($table, $rolePivot, $modelPivot, $source, $targets);
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

    /**
     * @param  list<int|null>  $targetIds
     */
    private function copyAssignments(string $table, string $rolePivot, string $modelPivot, string $sourceName, array $targetIds): void
    {
        if ($sourceName === '') {
            return;
        }

        $sourceId = DB::table($table)->where('name', $sourceName)->where('guard_name', 'web')->value('id');
        if (! $sourceId) {
            return;
        }

        $targetIds = array_values(array_unique(array_filter($targetIds)));
        if ($targetIds === []) {
            return;
        }

        $roleIds = DB::table($rolePivot)->where('permission_id', $sourceId)->pluck('role_id');
        foreach ($roleIds as $roleId) {
            foreach ($targetIds as $permissionId) {
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

        $direct = DB::table($modelPivot)->where('permission_id', $sourceId)->get();
        foreach ($direct as $row) {
            foreach ($targetIds as $permissionId) {
                $exists = DB::table($modelPivot)
                    ->where('permission_id', $permissionId)
                    ->where('model_type', $row->model_type)
                    ->where('model_id', $row->model_id)
                    ->exists();
                if (! $exists) {
                    DB::table($modelPivot)->insert([
                        'permission_id' => $permissionId,
                        'model_type' => $row->model_type,
                        'model_id' => $row->model_id,
                    ]);
                }
            }
        }
    }
};
