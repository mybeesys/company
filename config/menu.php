<?php

return [

    [
        'name' => 'dashboard',
        'url' => '/dashboard',
        'icon' => 'fas fa-home',
        'permission' => \Modules\Employee\Support\DashboardHubPermissions::menuShowAny(),
        'subMenu' => [],
    ],

    [
        'name' => 'my_companies',
        'url' => '/my-companies',
        'icon' => 'fas fa-building',
        'permission' => \Modules\Employee\Support\MyCompaniesPermissions::SHOW,
        'subMenu' => [],
    ],

    [
        'name' => 'referrals',
        'url' => '/referrals',
        'icon' => 'fas fa-gift',
        'permission' => \Modules\Employee\Support\ReferralsPermissions::SHOW,
        'subMenu' => [],
    ],

    [
        'name' => 'franchise',
        'url' => '/franchise/companies',
        'icon' => 'fas fa-certificate',
        'permission' => \Modules\Franchise\Support\FranchisePermissions::menuShowAny(),
        'subMenu' => [
            // Temporarily hidden — unified hub at franchise/companies with horizontal tabs
            // [
            //     'name' => 'franchise_com',
            //     'url' => 'franchise/companies',
            //     'permission' => 'Franchise Companies.all.show',
            // ],
            // [
            //     'name' => 'branches_mgmt',
            //     'url' => 'franchise/companies?tab=branches',
            //     'permission' => 'Franchise Companies.all.show',
            // ],
            // [
            //     'name' => 'franchise_product_mgmt',
            //     'url' => 'franchise/companies?tab=products',
            //     'permission' => 'Franchise Companies.all.show',
            // ],
            // [
            //     'name' => 'custom_menus_mgmt',
            //     'url' => 'franchise/companies?tab=menus',
            //     'permission' => 'Franchise Companies.all.show',
            // ],
        ],
    ],

    [
        'name' => 'product_module',
        'url' => 'category',
        'icon' => 'fas fa-shopping-cart',
        'permission' => 'products.all.show',
        'subMenu' => [
            // Temporarily hidden — dashboards are in main /dashboard hub tabs
            // [
            //     'name' => 'product_dashboard',
            //     'url' => 'dashboard?tab=products',
            //     'permission' => 'products.dashboard.show',
            // ],
            [

                'name' => 'products',
                'url' => 'category',
                'permission' => 'products.category.show',

            ],

            [

                'name' => 'ingredient',
                'url' => 'ingredient',
                'permission' => 'products.ingredient.show',

            ],
            [
                'name' => 'modifiers',
                'url' => 'modifier',
                'permission' => 'products.modifier.show',

            ],
            [
                'name' => 'attribute',
                'url' => 'attribute',
                'permission' => 'products.attribute.show',

            ],
            [
                'name' => 'customMenu',
                'url' => 'customMenu',
                'permission' => 'products.custom menu.show',

            ],
            // Temporarily hidden from sidebar (route remains available)
            // [
            //     'name' => 'serviceFee',
            //     'url' => 'serviceFee',
            //     'permission' => 'products.service fee.show',
            // ],
            [
                'name' => 'type-service',
                'url' => 'type-service',
                'permission' => 'products.type service.show',

            ],

            [
                'name' => 'priceTier',
                'url' => 'priceTier',
                'permission' => 'products.price tier.show',

            ],
            [
                'name' => 'discount',
                'url' => 'discount',
                'permission' => 'products.discount.show',

            ],
            // [
            //     'name' => 'linkedCombo',
            //     'url' => 'linkedCombo',
            //     'permission' => '',

            // ],
            [
                'name' => 'productBarcode',
                'url' => 'productBarcode/barcode',
                'permission' => 'products.product barcode.show',

            ],
            [
                'name' => 'importProduct',
                'url' => 'importProduct/import',
                'permission' => 'products.importProduct.show',

            ],

        ],
    ],
    [
        'name' => 'inventory_module',
        'url' => '#',
        'permission' => 'inventory.all.show',
        'icon' => 'fas fa-warehouse',
        'subMenu' => [
            [
                'name' => 'inventory',
                'url' => 'productInventory',
                'permission' => 'inventory.product.show',
            ],
            [
                'name' => 'prep',
                'url' => 'prep',
                'permission' => 'inventory.prep.show',
            ],
            [
                'name' => 'transfer',
                'url' => 'transfer',
                'permission' => 'inventory.transfer.show',
            ],
            [
                'name' => 'waste',
                'url' => 'waste',
                'permission' => 'inventory.waste.show',
            ],
            [
                'name' => 'import',
                'url' => 'openInventoryImport/import',
                'permission' => 'inventory.import.show',
            ],
        ],
    ],
    [
        'name' => 'sales',
        'url' => '#',
        'icon' => 'fas fa-dollar-sign',
        'permission' => 'sales.all.show',
        'subMenu' => [
            [
                'name' => 'quotations',
                'url' => 'quotations',
                'permission' => 'sales.Quotations.show',
            ],
            [
                'name' => 'invoices',
                'url' => 'invoices',
                'permission' => 'sales.Sell invoices.show',
            ],
            [
                'name' => 'sell-return',
                'url' => 'sell-return',
                'permission' => 'sales.Sell returns.show',
            ],
            ...(config('zatca.show_in_menu', true) ? [[
                'name' => 'zatca_einvoicing',
                'url' => 'zatca-einvoicing',
                'permission' => 'zatca.E-invoicing.show',
            ]] : []),
            [
                'name' => 'clients',
                'url' => 'clients',
                'permission' => 'sales.Customers.show',
            ],
            [
                'name' => 'customer_receipts',
                'url' => 'receipts',
                'permission' => 'sales.Customer receipts.show',
            ],
            [
                'name' => 'coupons',
                'url' => 'coupon',
                'permission' => 'sales.coupons.show',
            ],
        ],
    ],
    [
        'name' => 'purchases',
        'url' => '#',
        'icon' => 'fas fa-shopping-cart',
        'permission' => 'purchases.all.show',
        'subMenu' => [
            [
                'name' => 'suppliers',
                'url' => 'suppliers',
                'permission' => 'purchases.Suppliers.show',
            ],
            [
                'name' => 'purchase-order',
                'url' => 'purchases-order',
                'permission' => 'purchases.Purchase Orders.show',
            ],
            [
                'name' => 'purchase_invoices',
                'url' => 'purchase-invoices',
                'permission' => 'purchases.Purchase invoices.show',
            ],
            [
                'name' => 'purchases-return',
                'url' => 'purchases-return',
                'permission' => 'purchases.Purchase returns.show',
            ],
            [
                'name' => 'supplier_receipts',
                'url' => 'suppliers-receipts',
                'permission' => 'purchases.Supplier vouchers.show',
            ],
        ],
    ],

    [
        'name' => 'accounting_module',
        'url' => 'tree-of-accounts',
        'icon' => 'fas fa-calculator',
        'permission' => 'accounting.all.show',
        'subMenu' => [
            // Temporarily hidden — dashboards are in main /dashboard hub tabs
            // [
            //     'name' => 'accounting_dashboard',
            //     'url' => 'dashboard?tab=accounting',
            //     'permission' => 'accounting.Dashboard.show',
            // ],
            [
                'name' => 'chart_of_accounts',
                'url' => 'tree-of-accounts',
                'permission' => 'accounting.Accounts tree.show',
            ],
            [
                'name' => 'journalEntry',
                'url' => 'journal-entry-index',
                'permission' => 'accounting.Daily entries.show',
            ],
            [
                'name' => 'receipt_vouchers',
                'url' => 'receipt-vouchers',
                'permission' => 'accounting.Receipt vouchers.show',
            ],
            [
                'name' => 'payment_vouchers',
                'url' => 'payment-vouchers',
                'permission' => 'accounting.Payment vouchers.show',
            ],
            [
                'name' => 'expenses_manage',
                'url' => 'expenses/manage',
                'permission' => 'accounting.Expenses.show',
            ],
            [
                'name' => 'costCenter',
                'url' => 'cost-center-index',
                'permission' => 'accounting.Cost center.show',
            ],
            [
                'name' => 'periodic',
                'url' => 'inventory/periodic-inventory',
                'permission' => 'accounting.Periodic inventory.show',
            ],
            [
                'name' => 'accounting_settings',
                'url' => 'accounting-settings',
                'permission' => ['accounting.Settings.show', 'accounting.Accounts routing.show'],
            ],
            [
                'name' => 'accounting_reports',
                'url' => 'accounting-reports',
                'permission' => \Modules\Accounting\Support\AccountingPermissions::reportShowAny(),
            ],
        ],
    ],

    // [
    //     'name' => 'establishments_module',
    //     'url' => '',
    //     'permission' => '',
    //     'icon' => 'fas fa-building',
    //     'subMenu' => [
    //         [
    //             'name' => 'establishments',
    //             'url' => 'establishment',
    //             'permission' => 'establishments.establishments.show',
    //         ],
    //         [
    //             'name' => 'areas',
    //             'url' => 'area',
    //             'permission' => '',
    //         ],
    //         [
    //             'name' => 'tables',
    //             'url' => 'table',
    //             'permission' => '',
    //         ],
    //         [
    //             'name' => 'tables_qr',
    //             'url' => 'areaQR',
    //             'permission' => '',
    //         ],
    //         [
    //             'name' => 'menu_qr',
    //             'url' => 'menuQR',
    //             'permission' => '',
    //         ],
    //     ]
    // ],

    [
        'name' => 'clients_suppliers_module',
        'url' => '/',
        'icon' => 'fas fa-users',
        'permission' => 'sales.Customers.show',
        'subMenu' => [
            [
                'name' => 'clients_suppliers_settings',
                'url' => 'client-supplier-setting',
                'permission' => 'sales.Customers.show',
            ],
        ],
    ],
    [

        'name' => 'employees_management_module',
        'url' => '',
        'icon' => 'fas fa-id-card',
        'permission' => \Modules\Employee\Support\EmployeePermissions::menuShowAny(),
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
                ],
            ],
        ],
    ],
    [
        'name' => 'screen_module',
        'url' => 'main',
        'icon' => 'fas fa-desktop',
        'permission' => \Modules\Screen\Support\ScreenPermissions::menuShowAny(),
        'subMenu' => [],
    ],
    [
        'name' => 'reports_module',
        'url' => 'payment-reports',
        'icon' => 'fas fa-bar-chart',
        'permission' => \Modules\Report\Support\ReportPermissions::menuShowAny(),
        'subMenu' => [],
    ],
    [
        'name' => 'setting',
        'url' => '',
        'icon' => 'fas fa-cog',
        'permission' => \Modules\General\Support\SettingPermissions::menuShowAny(),
        'subMenu' => [
            [
                'name' => 'general_setting',
                'url' => 'general-setting',
                'permission' => \Modules\General\Support\SettingPermissions::pageShowAny(),

            ],
            ...(config('zatca.show_in_menu', true) ? [[
                'name' => 'zatca_settings',
                'url' => 'zatca-settings',
                'permission' => ['zatca.Settings.show', 'zatca.Operations.show'],
            ]] : []),
            [
                'name' => 'establishments',
                'url' => 'establishment',
                'permission' => ['establishments.establishments.show', 'establishments.establishment.show'],
            ],
            [
                'name' => 'cashier_payment_methods',
                'url' => 'settings/cashier-payment-methods',
                'permission' => 'establishments.establishment.update',
            ],
            [
                'name' => 'internal_consumption_types',
                'url' => 'settings/internal-consumption-types',
                'permission' => 'establishments.establishment.update',
            ],
            [
                'name' => 'cashier_service_fees',
                'url' => 'settings/service-fees',
                'permission' => 'establishments.establishment.update',
            ],
            /*      [
                'name' => 'devices',
                'url' => 'devices',
                'permission' => '',
            ],*/
            [
                'name' => 'tables',
                'url' => 'table',
                'permission' => [
                    'setting.tables.show',
                    'setting.tables_qr.show',
                    'setting.menu_qr.show',
                    'setting.menu_feedback.show',
                    'establishments.establishments.show',
                ],
                'subMenu' => [
                    [
                        'name' => 'tables',
                        'url' => 'table',
                        'permission' => 'setting.tables.show',
                    ],
                    [
                        'name' => 'areas',
                        'url' => 'area',
                        'permission' => ['establishments.establishments.show', 'setting.tables.show'],
                    ],
                    [
                        'name' => 'tables_qr',
                        'url' => 'areaQR',
                        'permission' => 'setting.tables_qr.show',
                    ],
                    [
                        'name' => 'menu_qr',
                        'url' => 'menuQR',
                        'permission' => 'setting.menu_qr.show',
                    ],
                    [
                        'name' => 'menu_feedback',
                        'url' => 'menu-feedback',
                        'permission' => \Modules\General\Support\SettingPermissions::for('menu_feedback', 'show'),
                    ],
                ],

            ],

        ],

    ],
];
