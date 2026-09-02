<?php

namespace Modules\Report\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Modules\Report\Support\ReportPermissions;
use Spatie\Permission\PermissionRegistrar;

/**
 * Upserts general-reports EMS permissions without truncating other permissions.
 */
class ReportPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $created = [];

        foreach (ReportPermissions::definitions() as $row) {
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
            [ReportPermissions::ALL_SHOW],
            array_filter(array_map(
                static fn (string $name) => $byName->get($name),
                ReportPermissions::reportShows()
            ))
        );
        $this->grantToRolesWith(
            [ReportPermissions::ALL_PRINT],
            array_filter(array_map(
                static fn (string $name) => $byName->get($name),
                ReportPermissions::reportPrints()
            ))
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
