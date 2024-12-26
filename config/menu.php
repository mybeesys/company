<?php
return [
    [
        'name' => 'dashboard',
        'url' => 'dashboard',
        'icon' => 'fas fa-home',
        'permission_model' => '',
        'subMenu' => []
    ],
    [
        'name' => 'product_module',
        'url' => 'category',
        'icon' => 'fas fa-shopping-cart',
        'permission_model' => '',
        'subMenu' => [
            [

                'name' => 'products',
                'url' => 'category',
                'permission_model' => '',

            ],
            [
                'name' => 'modifiers',
                'url' => 'modifier',
                'permission_model' => '',

            ],
            [
                'name' => 'attribute',
                'url' => 'attribute',
                'permission_model' => '',

            ],
            [
                'name' => 'customMenu',
                'url' => 'customMenu',
                'permission_model' => '',

            ],
            [
                'name' => 'serviceFee',
                'url' => 'serviceFee',
                'permission_model' => '',

            ],
            [
                'name' => 'discount',
                'url' => 'discount',
                'permission_model' => '',

            ],
            [
                'name' => 'linkedCombo',
                'url' => 'linkedCombo',
                'permission_model' => '',

            ],
            [
                'name' => 'ingredient',
                'url' => 'ingredient',
                'permission_model' => '',

            ],


        ]
    ],
    [
        'name' => 'inventory_module',
        'url' => 'productInventory',
        'permission_model' => '',
        'icon' => 'fas fa-building',
        'subMenu' => [
            [
                'name' => 'inventory',
                'url' => 'productInventory',
                'permission_model' => '',
            ],
            [
                'name' => 'ingredientInventory',
                'url' => 'ingredientInventory',
                'permission_model' => '',
            ],
            [
                'name' => 'po',
                'url' => 'purchaseOrder',
                'permission_model' => '',
            ],
            [
                'name' => 'prep',
                'url' => 'prep',
                'permission_model' => '',
            ],
            [
                'name' => 'rma',
                'url' => 'rma',
                'permission_model' => '',
            ],
            [
                'name' => 'waste',
                'url' => 'waste',
                'permission_model' => '',
            ],
            [
                'name' => 'transfer',
                'url' => 'transfer',
                'permission_model' => '',
            ]
        ]
    ],
    [
        'name' => 'sales',
        'url' => '#',
        'icon' => 'fas fa-dollar-sign',
        'permission_model' => '',
        'subMenu' => [
            [
                'name' => 'clients',
                'url' => 'clients',
                'permission_model' => '',
            ],
            [
                'name' => 'invoices',
                'url' => 'invoices',
                'permission_model' => '',
            ],
        ]
    ],
    [
        'name' => 'purchases',
        'url' => '#',
        'icon' => 'fas fa-shopping-cart',
        'permission_model' => '',
        'subMenu' => [
            [
                'name' => 'suppliers',
                'url' => 'suppliers',
                'permission_model' => '',
            ],
            [
                'name' => 'purchase_invoices',
                'url' => 'purchase-invoices',
                'permission_model' => '',
            ],
        ]
    ],

    [
        'name' => 'accounting_module',
        'url' => 'dashboard',
        'icon' => 'fas fa-calculator',
        'permission_model' => '',
        'subMenu' => [
            [
                'name' => 'accounting_dashboard',
                'url' => 'accounting-dashboard',
                'permission_model' => '',
            ],
            [
                'name' => 'chart_of_accounts',
                'url' => 'tree-of-accounts',
                'permission_model' => '',
            ],
            [
                'name' => 'journalEntry',
                'url' => 'journal-entry-index',
                'permission_model' => '',
            ],
            [
                'name' => 'costCenter',
                'url' => 'cost-center-index',
                'permission_model' => '',
            ],

        ]
    ],

    [
        'name' => 'establishments_module',
        'url' => '',
        'permission_model' => '',
        'icon' => 'fas fa-building',
        'subMenu' => [
            [
                'name' => 'company_settings',
                'url' => 'company/setting',
                'permission_model' => '',
            ],
            [
                'name' => 'establishments',
                'url' => 'establishment',
                'permission_model' => '',
            ],
        ]
    ],


    [
        'name' => 'crm',
        'url' => '/',
        'icon' => 'fas fa-cogs',
        'permission_model' => '',
        'subMenu' => []
    ],
    [

        'name' => 'employees_management_module',
        'url' => '',
        'icon' => 'fas fa-id-card',
        'permission_model' => '',
        'subMenu' => [
            [
                'name' => 'employees',
                'url' => 'employee',
                'permission_model' => \Modules\Employee\Models\Employee::class,
            ],
            [
                'name' => 'pos_roles',
                'url' => 'pos-role',
                'permission_model' => \Modules\Employee\Models\Role::class,
            ],
            [
                'name' => 'dashboard_roles',
                'url' => 'dashboard-role',
                'permission_model' => \Modules\Employee\Models\Role::class,
            ],
            [
                'name' => 'schedules',
                'url' => 'schedule',
                'permission_model' => '',
                'subMenu' => [
                    [
                        'name' => 'timesheet_rule',
                        'url' => 'schedule/timesheet-rule',
                        'permission_model' => \Modules\Employee\Models\TimeSheetRule::class,
                    ],
                    [
                        'name' => 'shift_schedule',
                        'url' => 'schedule/shift',
                        'permission_model' => \Modules\Employee\Models\Shift::class,
                    ],
                    [
                        'name' => 'employees_working_hours',
                        'url' => 'schedule/timecard',
                        'permission_model' => \Modules\Employee\Models\TimeCard::class,
                    ],
                    [
                        'name' => 'payroll',
                        'url' => 'schedule/payroll',
                        'permission_model' => \Modules\Employee\Models\Payroll::class,
                    ],
                    [
                        'name' => 'payroll_group',
                        'url' => 'schedule/payroll-group',
                        'permission_model' => \Modules\Employee\Models\PayrollGroup::class,
                    ],
                ]
            ],
        ]
    ],
    [
        'name' => 'reports_module',
        'url' => '/',
        'icon' => 'fas fa-bar-chart',
        'permission_model' => '',
        'subMenu' => []
    ],
];