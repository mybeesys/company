<?php

namespace Modules\Product\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Product\Support\ProductPermissions;
use Spatie\Permission\PermissionRegistrar;

/**
 * Upserts Products EMS permissions without truncating other permissions.
 */
class ProductPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $created = [];

        foreach (ProductPermissions::definitions() as $row) {
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

        $byName = collect($created)->keyBy('name');

        $admin = Employee::query()->where('email', 'admin@admin.com')->first()
            ?? Employee::query()->where('user_name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(collect($created));
        }

        $this->grantToRolesWith(
            [ProductPermissions::PRICE_TIER_CREATE, ProductPermissions::ALL_DELETE],
            array_filter([
                $byName->get(ProductPermissions::PRICE_TIER_DELETE),
            ])
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  list<string>  $requiredNames
     * @param  list<Permission>  $grant
     */
    private function grantToRolesWith(array $requiredNames, array $grant): void
    {
        if ($grant === []) {
            return;
        }

        $roles = DashboardRole::query()
            ->where('type', 'ems')
            ->whereHas('permissions', fn ($q) => $q->whereIn('name', $requiredNames))
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($grant);
        }
    }
}
