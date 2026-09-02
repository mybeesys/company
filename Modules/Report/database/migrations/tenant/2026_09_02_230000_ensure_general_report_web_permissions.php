<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Report\Support\ReportPermissions;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * @return list<array{name: string, name_ar: string}>
     */
    private function permissions(): array
    {
        return [
            ['name' => ReportPermissions::SELL_PAYMENT_SHOW, 'name_ar' => 'تقرير المبيعات'],
            ['name' => ReportPermissions::SELL_PAYMENT_PRINT, 'name_ar' => 'تقرير المبيعات'],
            ['name' => ReportPermissions::PRODUCT_SALES_SHOW, 'name_ar' => 'تقرير مبيعات الأصناف'],
            ['name' => ReportPermissions::PRODUCT_SALES_PRINT, 'name_ar' => 'تقرير مبيعات الأصناف'],
            ['name' => ReportPermissions::SALES_COMPARISON_SHOW, 'name_ar' => 'تقرير مقارنة المبيعات'],
            ['name' => ReportPermissions::SALES_COMPARISON_PRINT, 'name_ar' => 'تقرير مقارنة المبيعات'],
            ['name' => ReportPermissions::WEEKDAY_SALES_SHOW, 'name_ar' => 'مبيعات حسب يوم الأسبوع والتاريخ'],
            ['name' => ReportPermissions::WEEKDAY_SALES_PRINT, 'name_ar' => 'مبيعات حسب يوم الأسبوع والتاريخ'],
            ['name' => ReportPermissions::PURCHASE_PAYMENT_SHOW, 'name_ar' => 'تقرير المشتريات'],
            ['name' => ReportPermissions::PURCHASE_PAYMENT_PRINT, 'name_ar' => 'تقرير المشتريات'],
            ['name' => ReportPermissions::PRODUCT_PURCHASE_SHOW, 'name_ar' => 'تقرير مشتريات الأصناف'],
            ['name' => ReportPermissions::PRODUCT_PURCHASE_PRINT, 'name_ar' => 'تقرير مشتريات الأصناف'],
            ['name' => ReportPermissions::PRODUCT_INVENTORY_SHOW, 'name_ar' => 'تقرير عمليات المخزون'],
            ['name' => ReportPermissions::PRODUCT_INVENTORY_PRINT, 'name_ar' => 'تقرير عمليات المخزون'],
            ['name' => ReportPermissions::PRODUCT_INVENTORY_SUMMARY_SHOW, 'name_ar' => 'تقرير رصيد المخزون'],
            ['name' => ReportPermissions::PRODUCT_INVENTORY_SUMMARY_PRINT, 'name_ar' => 'تقرير رصيد المخزون'],
            ['name' => ReportPermissions::PRODUCT_STOCK_SHOW, 'name_ar' => 'تقرير مخزون الصنف'],
            ['name' => ReportPermissions::PRODUCT_STOCK_PRINT, 'name_ar' => 'تقرير مخزون الصنف'],
            ['name' => ReportPermissions::PROFIT_LOSS_SHOW, 'name_ar' => 'الربح والخسارة'],
            ['name' => ReportPermissions::PROFIT_LOSS_PRINT, 'name_ar' => 'الربح والخسارة'],
            ['name' => ReportPermissions::PURCHASE_SELL_SHOW, 'name_ar' => 'المشتريات والمبيعات'],
            ['name' => ReportPermissions::PURCHASE_SELL_PRINT, 'name_ar' => 'المشتريات والمبيعات'],
            ['name' => ReportPermissions::REGISTER_SHOW, 'name_ar' => 'تقرير السجل النقدي'],
            ['name' => ReportPermissions::REGISTER_PRINT, 'name_ar' => 'تقرير السجل النقدي'],
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

        $showIds = [];
        $printIds = [];
        foreach ($this->permissions() as $row) {
            $id = $ids[$row['name']] ?? null;
            if (! $id) {
                continue;
            }
            if (str_ends_with($row['name'], '.show')) {
                $showIds[] = $id;
            }
            if (str_ends_with($row['name'], '.print')) {
                $printIds[] = $id;
            }
        }

        $this->copyAssignments($table, $rolePivot, $modelPivot, ReportPermissions::ALL_SHOW, $showIds);
        $this->copyAssignments($table, $rolePivot, $modelPivot, ReportPermissions::ALL_PRINT, $printIds);

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
