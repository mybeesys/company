<?php
return [
    [
        'name' => 'dashboard',
        'url' => 'dashboard',
        'icon' => 'fas fa-home',
        'permission' => '',
        'subMenu' => []
    ],
    [
        'name' => 'product_module',
        'url' => 'category',
        'icon' => 'fas fa-shopping-cart',
        'permission' => '',
        'subMenu' => [
            [

                'name' => 'products',
                'url' => 'category',
                'permission' => '',

            ],
            [
                'name' => 'modifiers',
                'url' => 'modifier',
                'permission' => '',

            ],
            [
                'name' => 'attribute',
                'url' => 'attribute',
                'permission' => '',

            ],
            [
                'name' => 'customMenu',
                'url' => 'customMenu',
                'permission' => '',

            ],
            [
                'name' => 'serviceFee',
                'url' => 'serviceFee',
                'permission' => '',

            ],
            [
                'name' => 'discount',
                'url' => 'discount',
                'permission' => '',

            ],
            [
                'name' => 'linkedCombo',
                'url' => 'linkedCombo',
                'permission' => '',

            ],
            [
                'name' => 'ingredient',
                'url' => 'ingredient',
                'permission' => '',

            ],


        ]
    ],
    [
        'name' => 'inventory_module',
        'url' => 'productInventory',
        'permission' => '',
        'icon' => 'fas fa-building',
        'subMenu' => [
            [
                'name' => 'inventory',
                'url' => 'productInventory',
                'permission' => '',
            ],
            [
                'name' => 'ingredientInventory',
                'url' => 'ingredientInventory',
                'permission' => '',
            ],
            [
                'name' => 'po',
                'url' => 'purchaseOrder',
                'permission' => '',
            ],
            [
                'name' => 'prep',
                'url' => 'prep',
                'permission' => '',
            ],
            [
                'name' => 'rma',
                'url' => 'rma',
                'permission' => '',
            ],
            [
                'name' => 'waste',
                'url' => 'waste',
                'permission' => '',
            ],
            [
                'name' => 'transfer',
                'url' => 'transfer',
                'permission' => '',
            ]
        ]
    ],
    [
        'name' => 'sales',
        'url' => '#',
        'icon' => 'fas fa-dollar-sign',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'clients',
                'url' => 'clients',
                'permission' => '',
            ],
            [
                'name' => 'invoices',
                'url' => 'invoices',
                'permission' => '',
            ],
        ]
    ],
    [
        'name' => 'purchases',
        'url' => '#',
        'icon' => 'fas fa-shopping-cart',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'suppliers',
                'url' => 'suppliers',
                'permission' => '',
            ],
            [
                'name' => 'purchase_invoices',
                'url' => 'purchase-invoices',
                'permission' => '',
            ],
        ]
    ],

    [
        'name' => 'accounting_module',
        'url' => 'dashboard',
        'icon' => 'fas fa-calculator',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'accounting_dashboard',
                'url' => 'accounting-dashboard',
                'permission' => '',
            ],
            [
                'name' => 'chart_of_accounts',
                'url' => 'tree-of-accounts',
                'permission' => '',
            ],
            [
                'name' => 'journalEntry',
                'url' => 'journal-entry-index',
                'permission' => '',
            ],
            [
                'name' => 'costCenter',
                'url' => 'cost-center-index',
                'permission' => '',
            ],

        ]
    ],

    [
        'name' => 'establishments_module',
        'url' => '',
        'permission' => '',
        'icon' => 'fas fa-building',
        'subMenu' => [
            [
                'name' => 'company_settings',
                'url' => 'company/setting',
                'permission' => 'establishments.company.show',
            ],
            [
                'name' => 'establishments',
                'url' => 'establishment',
                'permission' => 'establishments.establishments.show',
            ],
        ]
    ],


    [
        'name' => 'crm',
        'url' => '/',
        'icon' => 'fas fa-cogs',
        'permission' => '',
        'subMenu' => []
    ],
    [

        'name' => 'employees_management_module',
        'url' => '',
        'icon' => 'fas fa-id-card',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'employees',
                'url' => 'employee',
                'permission' => 'employees.employees.show',
            ],
            [
                'name' => 'pos_roles',
                'url' => 'pos-role',
                'permission' => 'employees.pos_roles.show',
            ],
            [
                'name' => 'dashboard_roles',
                'url' => 'dashboard-role',
                'permission' => 'employees.dashboard_roles.show',
            ],
            [
                'name' => 'adjustments',
                'url' => 'adjustment',
                'permission' => 'employees.allowances_deductions.show',
            ],
            [
                'name' => 'schedules',
                'url' => 'schedule',
                'permission' => '',
                'subMenu' => [
                    [
                        'name' => 'timesheet_rule',
                        'url' => 'schedule/timesheet-rule',
                        'permission' => 'employees.time_sheet_rules.show',
                    ],
                    [
                        'name' => 'shift_schedule',
                        'url' => 'schedule/shift',
                        'permission' => 'employees.shifts.show',
                    ],
                    [
                        'name' => 'employees_working_hours',
                        'url' => 'schedule/timecard',
                        'permission' => 'employees.timecards.show',
                    ],
                    [
                        'name' => 'payroll',
                        'url' => 'schedule/payroll',
                        'permission' => ['employees.payrolls.show', 'employees.payrolls_groups.show'],
                    ],
                ]
            ],
        ]
    ],
    [
        'name' => 'reports_module',
        'url' => '/',
        'icon' => 'fas fa-bar-chart',
        'permission' => '',
        'subMenu' => []
    ],
    [
        'name' => 'general_setting',
        'url' => '/',
        'icon' => 'fas fa-cog',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'taxes',
                'url' => 'taxes',
                'permission' => '',
            ],
        ]
    ],
];