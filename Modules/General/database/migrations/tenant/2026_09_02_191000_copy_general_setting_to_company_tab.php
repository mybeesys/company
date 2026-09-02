<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Support\EstablishmentPermissions;
use Modules\General\Support\SettingPermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('permission.table_names.permissions', 'permissions');
        $rolePivot = config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $modelPivot = config('permission.table_names.model_has_permissions', 'model_has_permissions');

        $this->copyAssignments(
            $table,
            $rolePivot,
            $modelPivot,
            SettingPermissions::GENERAL_SHOW,
            $this->permissionId($table, EstablishmentPermissions::COMPANY_SHOW)
        );
        $this->copyAssignments(
            $table,
            $rolePivot,
            $modelPivot,
            SettingPermissions::GENERAL_UPDATE,
            $this->permissionId($table, EstablishmentPermissions::COMPANY_UPDATE)
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Assignments copied onto existing company permissions are left in place.
    }

    private function permissionId(string $table, string $name): ?int
    {
        $id = DB::table($table)->where('name', $name)->where('guard_name', 'web')->value('id');

        return $id ? (int) $id : null;
    }

    private function copyAssignments(string $table, string $rolePivot, string $modelPivot, string $sourceName, ?int $targetId): void
    {
        if (! $targetId) {
            return;
        }

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
