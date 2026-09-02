<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Support\AccountingPermissions;

return new class extends Migration
{
    /**
     * @return list<array{name: string, name_ar: string}>
     */
    private function permissions(): array
    {
        return [
            ['name' => AccountingPermissions::RECEIPT_UPDATE, 'name_ar' => 'سندات القبض'],
            ['name' => AccountingPermissions::RECEIPT_DELETE, 'name_ar' => 'سندات القبض'],
            ['name' => AccountingPermissions::PAYMENT_UPDATE, 'name_ar' => 'سندات الصرف'],
            ['name' => AccountingPermissions::PAYMENT_DELETE, 'name_ar' => 'سندات الصرف'],
            ['name' => AccountingPermissions::EXPENSES_SHOW, 'name_ar' => 'المصاريف'],
            ['name' => AccountingPermissions::EXPENSES_CREATE, 'name_ar' => 'المصاريف'],
            ['name' => AccountingPermissions::EXPENSES_UPDATE, 'name_ar' => 'المصاريف'],
            ['name' => AccountingPermissions::EXPENSES_DELETE, 'name_ar' => 'المصاريف'],
            ['name' => AccountingPermissions::PERIODIC_SHOW, 'name_ar' => 'الجرد الدوري'],
            ['name' => AccountingPermissions::PERIODIC_PRINT, 'name_ar' => 'الجرد الدوري'],
            ['name' => AccountingPermissions::PERIODIC_CREATE, 'name_ar' => 'الجرد الدوري'],
            ['name' => AccountingPermissions::PERIODIC_UPDATE, 'name_ar' => 'الجرد الدوري'],
            ['name' => AccountingPermissions::SETTINGS_SHOW, 'name_ar' => 'إعدادات المحاسبة'],
            ['name' => AccountingPermissions::SETTINGS_UPDATE, 'name_ar' => 'إعدادات المحاسبة'],
            ['name' => AccountingPermissions::EXPENSE_REPORT_SHOW, 'name_ar' => 'تقرير المصاريف'],
            ['name' => AccountingPermissions::EXPENSE_REPORT_PRINT, 'name_ar' => 'تقرير المصاريف'],
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

        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::RECEIPT_CREATE, [
            $ids[AccountingPermissions::RECEIPT_UPDATE] ?? null,
            $ids[AccountingPermissions::RECEIPT_DELETE] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::PAYMENT_CREATE, [
            $ids[AccountingPermissions::PAYMENT_UPDATE] ?? null,
            $ids[AccountingPermissions::PAYMENT_DELETE] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::PAYMENT_SHOW, [
            $ids[AccountingPermissions::EXPENSES_SHOW] ?? null,
            $ids[AccountingPermissions::PERIODIC_SHOW] ?? null,
            $ids[AccountingPermissions::EXPENSE_REPORT_SHOW] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::PAYMENT_CREATE, [
            $ids[AccountingPermissions::EXPENSES_CREATE] ?? null,
            $ids[AccountingPermissions::EXPENSES_UPDATE] ?? null,
            $ids[AccountingPermissions::EXPENSES_DELETE] ?? null,
            $ids[AccountingPermissions::PERIODIC_CREATE] ?? null,
            $ids[AccountingPermissions::PERIODIC_UPDATE] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::PAYMENT_PRINT, [
            $ids[AccountingPermissions::PERIODIC_PRINT] ?? null,
            $ids[AccountingPermissions::EXPENSE_REPORT_PRINT] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::ALL_SHOW, [
            $ids[AccountingPermissions::SETTINGS_SHOW] ?? null,
            $ids[AccountingPermissions::EXPENSES_SHOW] ?? null,
            $ids[AccountingPermissions::PERIODIC_SHOW] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::ALL_UPDATE, [
            $ids[AccountingPermissions::SETTINGS_UPDATE] ?? null,
        ]);
        $this->copyAssignments($table, $rolePivot, $modelPivot, AccountingPermissions::ROUTING_UPDATE, [
            $ids[AccountingPermissions::SETTINGS_UPDATE] ?? null,
        ]);
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
