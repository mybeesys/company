<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Product\Support\ProductPermissions;

return new class extends Migration
{
    /**
     * @return list<array{name: string, name_ar: string}>
     */
    private function permissions(): array
    {
        return [
            [
                'name' => ProductPermissions::PRICE_TIER_DELETE,
                'name_ar' => 'التسعيرات',
            ],
        ];
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

        $this->copyAssignments(
            $table,
            $rolePivot,
            $modelPivot,
            ProductPermissions::PRICE_TIER_CREATE,
            [
                $ids[ProductPermissions::PRICE_TIER_DELETE] ?? null,
            ]
        );
    }

    public function down(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');

        DB::table($table)
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->where('guard_name', 'web')
            ->where('type', 'ems')
            ->delete();
    }

    /**
     * @param  list<int|null>  $targetIds
     */
    private function copyAssignments(string $table, string $rolePivot, string $modelPivot, string $sourceName, array $targetIds): void
    {
        $sourceId = DB::table($table)->where('name', $sourceName)->where('guard_name', 'web')->value('id');
        if (! $sourceId) {
            return;
        }

        $targetIds = array_values(array_filter($targetIds));
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
