<?php

namespace Modules\Inventory\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Inventory\Support\InventoryPermissions;
use Spatie\Permission\PermissionRegistrar;

/**
 * Upserts Inventory EMS permissions without truncating other permissions.
 */
class InventoryPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $created = [];

        foreach (InventoryPermissions::definitions() as $row) {
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

        $admin = Employee::query()->where('email', 'admin@admin.com')->first()
            ?? Employee::query()->where('user_name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(collect($created));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
