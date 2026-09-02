<?php

namespace Tests\Unit;

use Modules\Employee\Support\EmployeePermissions;
use PHPUnit\Framework\TestCase;

class EmployeePermissionsTest extends TestCase
{
    public function test_maps_sidebar_entities_to_existing_ems_names(): void
    {
        $this->assertSame('employees.employees.show', EmployeePermissions::crud('employees')['show']);
        $this->assertSame('employees.employee.create', EmployeePermissions::crud('employee')['create']);
        $this->assertSame('employees.pos_roles.show', EmployeePermissions::crud('pos_roles')['show']);
        $this->assertSame('employees.dashboard_role.update', EmployeePermissions::crud('dashboard_role')['update']);
        $this->assertSame('employees.allowances_deductions.show', EmployeePermissions::crud('allowances_deductions')['show']);
        $this->assertSame('employees.time_sheet_rules.update', EmployeePermissions::crud('time_sheet_rules')['update']);
        $this->assertSame('employees.shifts.print', EmployeePermissions::crud('shifts')['print']);
        $this->assertSame('employees.timecard.delete', EmployeePermissions::crud('timecard')['delete']);
        $this->assertSame('employees.payroll.create', EmployeePermissions::crud('payroll')['create']);
        $this->assertSame('employees.payrolls_group.delete', EmployeePermissions::crud('payrolls_group')['delete']);
    }

    public function test_menu_parent_covers_list_level_show_permissions(): void
    {
        $any = EmployeePermissions::menuShowAny();

        $this->assertContains(EmployeePermissions::ALL_SHOW, $any);
        $this->assertContains(EmployeePermissions::EMPLOYEES_SHOW, $any);
        $this->assertContains(EmployeePermissions::DASHBOARD_ROLES_SHOW, $any);
        $this->assertContains(EmployeePermissions::PAYROLLS_SHOW, $any);
        $this->assertContains(EmployeePermissions::PAYROLLS_GROUPS_SHOW, $any);
    }

    public function test_catalog_keeps_snake_case_and_skips_duplicate_pos_role_delete(): void
    {
        $catalog = include dirname(__DIR__, 2).'/Modules/Employee/data/dashboard-permissions.php';
        $names = array_column($catalog, 'name');

        foreach ([
            EmployeePermissions::EMPLOYEES_SHOW,
            EmployeePermissions::EMPLOYEE_UPDATE,
            EmployeePermissions::POS_ROLE_DELETE,
            EmployeePermissions::DASHBOARD_ROLES_PRINT,
            EmployeePermissions::PAYROLL_PRINT,
            EmployeePermissions::ALLOWANCE_CREATE,
        ] as $name) {
            $this->assertContains($name, $names);
        }

        $this->assertSame(
            1,
            count(array_filter($names, static fn ($name): bool => $name === EmployeePermissions::POS_ROLE_DELETE))
        );
    }

    public function test_rejects_entities_outside_employees_menu(): void
    {
        foreach (['warehouse', 'Dashboard', 'my_companies', 'referrals'] as $entity) {
            try {
                EmployeePermissions::crud($entity);
                $this->fail("Expected unknown employees EMS entity [{$entity}]");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($entity, $e->getMessage());
            }
        }
    }
}
