<?php

namespace Modules\Accounting\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Support\AccountingPermissions;
use Modules\Employee\Models\DashboardRole;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Upserts Accounting EMS permissions without truncating other permissions.
 */
class AccountingPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $created = [];

        foreach (AccountingPermissions::definitions() as $row) {
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
            [AccountingPermissions::RECEIPT_CREATE, AccountingPermissions::ALL_UPDATE],
            array_filter([$byName->get(AccountingPermissions::RECEIPT_UPDATE)])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::RECEIPT_CREATE, AccountingPermissions::ALL_DELETE],
            array_filter([$byName->get(AccountingPermissions::RECEIPT_DELETE)])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_CREATE, AccountingPermissions::ALL_UPDATE],
            array_filter([$byName->get(AccountingPermissions::PAYMENT_UPDATE)])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_CREATE, AccountingPermissions::ALL_DELETE],
            array_filter([$byName->get(AccountingPermissions::PAYMENT_DELETE)])
        );

        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_SHOW, AccountingPermissions::ALL_SHOW],
            array_filter([
                $byName->get(AccountingPermissions::EXPENSES_SHOW),
                $byName->get(AccountingPermissions::PERIODIC_SHOW),
                $byName->get(AccountingPermissions::EXPENSE_REPORT_SHOW),
            ])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_CREATE, AccountingPermissions::ALL_CREATE],
            array_filter([
                $byName->get(AccountingPermissions::EXPENSES_CREATE),
                $byName->get(AccountingPermissions::PERIODIC_CREATE),
            ])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_CREATE, AccountingPermissions::ALL_UPDATE],
            array_filter([
                $byName->get(AccountingPermissions::EXPENSES_UPDATE),
                $byName->get(AccountingPermissions::PERIODIC_UPDATE),
            ])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_CREATE, AccountingPermissions::ALL_DELETE],
            array_filter([$byName->get(AccountingPermissions::EXPENSES_DELETE)])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::PAYMENT_PRINT, AccountingPermissions::ALL_PRINT],
            array_filter([
                $byName->get(AccountingPermissions::PERIODIC_PRINT),
                $byName->get(AccountingPermissions::EXPENSE_REPORT_PRINT),
            ])
        );

        $this->grantToRolesWith(
            [AccountingPermissions::ALL_SHOW],
            array_filter([$byName->get(AccountingPermissions::SETTINGS_SHOW)])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::ALL_UPDATE, AccountingPermissions::ROUTING_UPDATE],
            array_filter([$byName->get(AccountingPermissions::SETTINGS_UPDATE)])
        );

        $this->grantToRolesWith(
            [AccountingPermissions::RECEIVABLES_AGING_SHOW],
            array_filter([$byName->get(AccountingPermissions::RECEIVABLES_AGE_REPORT_SHOW)])
        );
        $this->grantToRolesWith(
            [AccountingPermissions::RECEIVABLES_AGING_PRINT],
            array_filter([$byName->get(AccountingPermissions::RECEIVABLES_AGE_REPORT_PRINT)])
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
