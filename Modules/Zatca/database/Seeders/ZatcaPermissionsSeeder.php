<?php

namespace Modules\Zatca\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Zatca\Support\ZatcaPermissions;
use Spatie\Permission\PermissionRegistrar;

/**
 * Upserts ZATCA EMS permissions without truncating other permissions.
 * Grants them to admin and to roles that already had legacy sales/setting access.
 */
class ZatcaPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $created = [];

        foreach (ZatcaPermissions::definitions() as $row) {
            $permission = Permission::updateOrCreate(
                [
                    'name' => $row['name'],
                    'type' => $row['type'] ?? 'ems',
                ],
                [
                    'name_ar' => $row['name_ar'] ?? '',
                    'description' => $row['description'] ?? '',
                    'description_ar' => $row['description_ar'] ?? '',
                    'type' => $row['type'] ?? 'ems',
                    'guard_name' => 'web',
                ]
            );
            $created[] = $permission;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionIds = collect($created)->pluck('id')->all();
        $permissionModels = Permission::query()->whereIn('id', $permissionIds)->get();

        $admin = Employee::query()->where('email', 'admin@admin.com')->first()
            ?? Employee::query()->where('user_name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissionModels);
        }

        $legacyNames = [
            'setting.General setting.show',
            'setting.General setting.update',
            'sales.Sell invoices.show',
            'setting.all.show',
            'sales.all.show',
        ];

        $roles = DashboardRole::query()
            ->where('type', 'ems')
            ->whereHas('permissions', fn ($q) => $q->whereIn('name', $legacyNames))
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($permissionModels);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
