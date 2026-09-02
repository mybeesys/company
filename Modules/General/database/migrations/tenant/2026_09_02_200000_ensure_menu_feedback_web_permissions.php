<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\General\Support\SettingPermissions;
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
                'name' => SettingPermissions::MENU_FEEDBACK_SHOW,
                'name_ar' => 'تقييمات المنيو',
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

        $targetId = $ids[SettingPermissions::MENU_FEEDBACK_SHOW] ?? null;
        if ($targetId) {
            $this->copyAssignments($table, $rolePivot, $modelPivot, SettingPermissions::MENU_QR_SHOW, $targetId);
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

    private function copyAssignments(string $table, string $rolePivot, string $modelPivot, string $sourceName, int $targetId): void
    {
        $sourceId = DB::table($table)->where('name', $sourceName)->where('guard_name', 'web')->value('id');
        if (! $sourceId) {
            return;
        }

        $roleIds = DB::table($rolePivot)->where('permission_id', $sourceId)->pluck('role_id');
        foreach ($roleIds as $roleId) {
            $exists = DB::table($rolePivot)
                ->where('role_id', $roleId)
                ->where('permission_id', $targetId)
                ->exists();
            if (! $exists) {
                DB::table($rolePivot)->insert([
                    'role_id' => $roleId,
                    'permission_id' => $targetId,
                ]);
            }
        }

        $direct = DB::table($modelPivot)->where('permission_id', $sourceId)->get();
        foreach ($direct as $row) {
            $exists = DB::table($modelPivot)
                ->where('permission_id', $targetId)
                ->where('model_type', $row->model_type)
                ->where('model_id', $row->model_id)
                ->exists();
            if (! $exists) {
                DB::table($modelPivot)->insert([
                    'permission_id' => $targetId,
                    'model_type' => $row->model_type,
                    'model_id' => $row->model_id,
                ]);
            }
        }
    }
};
