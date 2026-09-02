<?php

namespace Modules\Employee\Support;

/**
 * Central Employees EMS permission names (must match dashboard-permissions.php).
 * Existing snake_case entity names are kept; list vs item rows are separate on purpose.
 */
final class EmployeePermissions
{
    public const ALL_SHOW = 'employees.all.show';

    public const ALL_PRINT = 'employees.all.print';

    public const ALL_CREATE = 'employees.all.create';

    public const ALL_UPDATE = 'employees.all.update';

    public const ALL_DELETE = 'employees.all.delete';

    public const EMPLOYEES_SHOW = 'employees.employees.show';

    public const EMPLOYEES_PRINT = 'employees.employees.print';

    public const EMPLOYEE_SHOW = 'employees.employee.show';

    public const EMPLOYEE_PRINT = 'employees.employee.print';

    public const EMPLOYEE_CREATE = 'employees.employee.create';

    public const EMPLOYEE_UPDATE = 'employees.employee.update';

    public const EMPLOYEE_DELETE = 'employees.employee.delete';

    public const POS_ROLES_SHOW = 'employees.pos_roles.show';

    public const POS_ROLES_PRINT = 'employees.pos_roles.print';

    public const POS_ROLE_SHOW = 'employees.pos_role.show';

    public const POS_ROLE_CREATE = 'employees.pos_role.create';

    public const POS_ROLE_UPDATE = 'employees.pos_role.update';

    public const POS_ROLE_DELETE = 'employees.pos_role.delete';

    public const DASHBOARD_ROLES_SHOW = 'employees.dashboard_roles.show';

    public const DASHBOARD_ROLES_PRINT = 'employees.dashboard_roles.print';

    public const DASHBOARD_ROLE_SHOW = 'employees.dashboard_role.show';

    public const DASHBOARD_ROLE_CREATE = 'employees.dashboard_role.create';

    public const DASHBOARD_ROLE_UPDATE = 'employees.dashboard_role.update';

    public const DASHBOARD_ROLE_DELETE = 'employees.dashboard_role.delete';

    public const TIME_SHEET_RULES_SHOW = 'employees.time_sheet_rules.show';

    public const TIME_SHEET_RULES_UPDATE = 'employees.time_sheet_rules.update';

    public const SHIFTS_SHOW = 'employees.shifts.show';

    public const SHIFTS_UPDATE = 'employees.shifts.update';

    public const SHIFTS_PRINT = 'employees.shifts.print';

    public const TIMECARDS_SHOW = 'employees.timecards.show';

    public const TIMECARDS_PRINT = 'employees.timecards.print';

    public const TIMECARD_PRINT = 'employees.timecard.print';

    public const TIMECARD_CREATE = 'employees.timecard.create';

    public const TIMECARD_UPDATE = 'employees.timecard.update';

    public const TIMECARD_DELETE = 'employees.timecard.delete';

    public const PAYROLLS_SHOW = 'employees.payrolls.show';

    public const PAYROLLS_PRINT = 'employees.payrolls.print';

    public const PAYROLL_CREATE = 'employees.payroll.create';

    public const PAYROLL_PRINT = 'employees.payroll.print';

    public const PAYROLLS_GROUPS_SHOW = 'employees.payrolls_groups.show';

    public const PAYROLLS_GROUP_SHOW = 'employees.payrolls_group.show';

    public const PAYROLLS_GROUPS_PRINT = 'employees.payrolls_groups.print';

    public const PAYROLLS_GROUP_UPDATE = 'employees.payrolls_group.update';

    public const PAYROLLS_GROUP_DELETE = 'employees.payrolls_group.delete';

    public const ALLOWANCES_SHOW = 'employees.allowances_deductions.show';

    public const ALLOWANCE_CREATE = 'employees.allowance_deduction.create';

    public const ALLOWANCE_UPDATE = 'employees.allowance_deduction.update';

    public const ALLOWANCE_DELETE = 'employees.allowance_deduction.delete';

    /**
     * Sidebar parent: visible when the wildcard or any list-level show is granted.
     *
     * @return list<string>
     */
    public static function menuShowAny(): array
    {
        return [
            self::ALL_SHOW,
            self::EMPLOYEES_SHOW,
            self::POS_ROLES_SHOW,
            self::DASHBOARD_ROLES_SHOW,
            self::ALLOWANCES_SHOW,
            self::TIME_SHEET_RULES_SHOW,
            self::SHIFTS_SHOW,
            self::TIMECARDS_SHOW,
            self::PAYROLLS_SHOW,
            self::PAYROLLS_GROUPS_SHOW,
        ];
    }

    /**
     * Shared allowance-type lookup/store used from employees, payroll, and establishment.
     *
     * @return list<string>
     */
    public static function adjustmentTypeHelperAny(): array
    {
        return [
            self::ALLOWANCES_SHOW,
            self::ALLOWANCE_CREATE,
            self::ALLOWANCE_UPDATE,
            self::EMPLOYEE_CREATE,
            self::EMPLOYEE_UPDATE,
            self::PAYROLL_CREATE,
        ];
    }

    /**
     * @return array{show?: string, print?: string, create?: string, update?: string, delete?: string}
     */
    public static function crud(string $entity): array
    {
        return match ($entity) {
            'employees' => [
                'show' => self::EMPLOYEES_SHOW,
                'print' => self::EMPLOYEES_PRINT,
            ],
            'employee' => [
                'show' => self::EMPLOYEE_SHOW,
                'print' => self::EMPLOYEE_PRINT,
                'create' => self::EMPLOYEE_CREATE,
                'update' => self::EMPLOYEE_UPDATE,
                'delete' => self::EMPLOYEE_DELETE,
            ],
            'pos_roles' => [
                'show' => self::POS_ROLES_SHOW,
                'print' => self::POS_ROLES_PRINT,
            ],
            'pos_role' => [
                'show' => self::POS_ROLE_SHOW,
                'create' => self::POS_ROLE_CREATE,
                'update' => self::POS_ROLE_UPDATE,
                'delete' => self::POS_ROLE_DELETE,
            ],
            'dashboard_roles' => [
                'show' => self::DASHBOARD_ROLES_SHOW,
                'print' => self::DASHBOARD_ROLES_PRINT,
            ],
            'dashboard_role' => [
                'show' => self::DASHBOARD_ROLE_SHOW,
                'create' => self::DASHBOARD_ROLE_CREATE,
                'update' => self::DASHBOARD_ROLE_UPDATE,
                'delete' => self::DASHBOARD_ROLE_DELETE,
            ],
            'time_sheet_rules' => [
                'show' => self::TIME_SHEET_RULES_SHOW,
                'update' => self::TIME_SHEET_RULES_UPDATE,
            ],
            'shifts' => [
                'show' => self::SHIFTS_SHOW,
                'print' => self::SHIFTS_PRINT,
                'update' => self::SHIFTS_UPDATE,
            ],
            'timecards' => [
                'show' => self::TIMECARDS_SHOW,
                'print' => self::TIMECARDS_PRINT,
            ],
            'timecard' => [
                'print' => self::TIMECARD_PRINT,
                'create' => self::TIMECARD_CREATE,
                'update' => self::TIMECARD_UPDATE,
                'delete' => self::TIMECARD_DELETE,
            ],
            'payrolls' => [
                'show' => self::PAYROLLS_SHOW,
                'print' => self::PAYROLLS_PRINT,
            ],
            'payroll' => [
                'create' => self::PAYROLL_CREATE,
                'print' => self::PAYROLL_PRINT,
            ],
            'payrolls_groups' => [
                'show' => self::PAYROLLS_GROUPS_SHOW,
                'print' => self::PAYROLLS_GROUPS_PRINT,
            ],
            'payrolls_group' => [
                'show' => self::PAYROLLS_GROUP_SHOW,
                'update' => self::PAYROLLS_GROUP_UPDATE,
                'delete' => self::PAYROLLS_GROUP_DELETE,
            ],
            'allowances_deductions' => [
                'show' => self::ALLOWANCES_SHOW,
            ],
            'allowance_deduction' => [
                'create' => self::ALLOWANCE_CREATE,
                'update' => self::ALLOWANCE_UPDATE,
                'delete' => self::ALLOWANCE_DELETE,
            ],
            default => throw new \InvalidArgumentException("Unknown employees EMS entity [{$entity}]"),
        };
    }

    /**
     * @return list<array{name: string, name_ar: string, description: string, description_ar: string, type: string}>
     */
    public static function definitions(): array
    {
        $rows = array_filter(
            include base_path('Modules/Employee/data/dashboard-permissions.php'),
            static fn (array $row): bool => str_starts_with((string) ($row['name'] ?? ''), 'employees.')
        );

        $unique = [];
        foreach ($rows as $row) {
            $name = (string) ($row['name'] ?? '');
            if ($name === '' || isset($unique[$name])) {
                continue;
            }
            $unique[$name] = $row;
        }

        return array_values($unique);
    }
}
