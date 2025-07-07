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
        'permission' => 'products.all.show',
        'subMenu' => [
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
            [
                'name' => 'serviceFee',
                'url' => 'serviceFee',
                'permission' => 'products.service fee.show',

            ],
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
                'name' => 'import',
                'url' => 'importProduct/import',
                'permission' => 'products.import.show',

            ]

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
                'permission' => 'inventory.product.show',
            ],
            // [
            //     'name' => 'po',
            //     'url' => 'purchaseOrder',
            //     'permission' => 'inventory.purchaseOrder.show',
            // ],
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
            ]
        ]
    ],
    [
        'name' => 'sales',
        'url' => '#',
        'icon' => 'fas fa-dollar-sign',
        'permission' => 'sales.all.show',
        'subMenu' => [
            [
                'name' => 'clients',
                'url' => 'clients',
                'permission' => 'sales.Customers.show',
            ],
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
            [
                'name' => 'customer_receipts',
                'url' => 'receipts',
                'permission' => 'sales.Customer receipts.show',
            ],
            [
                'name' => 'coupons',
                'url' => 'coupon',
                'permission' => 'sales.coupons.show'
            ]
        ]
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



        ]
    ],

    [
        'name' => 'accounting_module',
        'url' => 'dashboard',
        'icon' => 'fas fa-calculator',
        'permission' => 'accounting.all.show',
        'subMenu' => [
            [
                'name' => 'accounting_dashboard',
                'url' => 'accounting-dashboard',
                'permission' => 'accounting.Dashboard.show',
            ],
            [
                'name' => 'chart_of_accounts',
                'url' => 'tree-of-accounts',
                'permission' => 'accounting.Accounts tree.show',
            ],
            [
                'name' => 'accounts-routing',
                'url' => 'accounts-routing',
                'permission' => 'accounting.Accounts routing.show',
            ],
            [
                'name' => 'journalEntry',
                'url' => 'journal-entry-index',
                'permission' => 'accounting.Daily entries.show',
            ],
            [
                'name' => 'costCenter',
                'url' => 'cost-center-index',
                'permission' => 'accounting.Cost center.show',
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

            //   [
            //     'name' => 'periodic',
            //     'url' => 'inventory/periodic-inventory',
            //     'permission' => 'accounting.Payment vouchers.show',
            // ],


        ]
    ],
    [
        'name' => 'accounting_reports',
        'url' => 'accounting-reports',
        'icon' => 'fas fa-bar-chart',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'trial-balance',
                'url' => 'trial-balance',
                'permission' => 'accountingReports.Trial balance.show',
            ],
            [
                'name' => 'income-statement',
                'url' => 'income-statement',
                'permission' => 'accountingReports.Income statement.show',
            ],
            [
                'name' => 'ledger',
                'url' => 'ledger',
                'permission' => 'accountingReports.Journal ledger.show',
            ],

            [
                'name' => 'balance_sheet',
                'url' => 'balance-sheet',
                'permission' => 'accountingReports.Balance sheet.show',
            ],
            [
                'name' => 'journal-report',
                'url' => 'journal-report',
                'permission' => 'accountingReports.Journal ledger.show',
            ],
            [
                'name' => 'cash-flow',
                'url' => 'cash-flow',
                'permission' => 'accountingReports.Cash flow.show',
            ],

            [
                'name' => 'customers-suppliers-statement',
                'url' => 'customers-suppliers-statement',
                'permission' => 'accountingReports.Customers suppliers statement.show',
            ],


            [
                'name' => 'account-receivable-ageing-report',
                'url' => 'account-receivable-ageing-report',
                'permission' => 'accountingReports.Receivables aging.show',
            ],
            [
                'name' => 'account-receivable-ageing-details',
                'url' => 'account-receivable-ageing-details',
                'permission' => 'accountingReports.Payables aging.show',
            ],
            [
                'name' => 'account-payable-ageing-report',
                'url' => 'account-payable-ageing-report',
                'permission' => 'accountingReports.Payables age report.show',
            ],

            [
                'name' => 'account-payable-ageing-details',
                'url' => 'account-payable-ageing-details',
                'permission' => 'accountingReports.Payables age report.show',
            ],







        ]
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
                'permission' => 'sales.Customers.show'
            ],
        ]
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
        'name' => 'screen_module',
        'url' => '/',
        'icon' => 'fas fa-desktop',
        'permission' => 'screen_module.all.show',
        'subMenu' => [
            [
                'name' => 'main',
                'url' => 'main',
                'permission' => 'screen_module.all.show',
            ],
        ]
    ],
    [
        'name' => 'reports_module',
        'url' => '/',
        'icon' => 'fas fa-bar-chart',
        'permission' => 'reports_module.all.show',
        'subMenu' => [

            [
                'name' => 'Profit-Loss',
                'url' => 'Profit-Loss',
                'permission' => 'reports_module.all.show',
            ],
            [
                'name' => 'sales',
                'url' => 'sales-report',
                'permission' => 'reports_module.all.show',
            ],
            [
                'name' => 'purchase-payment-report',
                'url' => 'purchase-payment-report',
                'permission' => 'reports_module.all.show',
            ],

            [
                'name' => 'sell-payment-report',
                'url' => 'sell-payment-report',
                'permission' => 'reports_module.all.show',
            ],
            [
                'name' => 'purchase-sell',
                'url' => 'purchase-sell',
                'permission' => 'reports_module.all.show',
            ],

            [
                'name' => 'product-sales-report',
                'url' => 'product-sales-report',
                'permission' => 'reports_module.all.show',
            ],

            [
                'name' => 'product-purchase-report',
                'url' => 'product-purchase-report',
                'permission' => 'reports_module.all.show',
            ],

            [
                'name' => 'purchase-payment-report',
                'url' => 'purchase-payment-report',
                'permission' => 'reports_module.all.show',
            ],

            [
                'name' => 'product-inventory-report',
                'url' => 'product-inventory-report',
                'permission' => 'reports_module.all.show',
            ],




        ]
    ],
    [
        'name' => 'setting',
        'url' => '',
        'icon' => 'fas fa-cog',
        'permission' => 'setting.all.show',
        'subMenu' => [
            [
                'name' => 'general_setting',
                'url' => 'general-setting',
                'permission' => 'setting.General setting.show',

            ],
            [
                'name' => 'establishments',
                'url' => 'establishment',
                'permission' => 'establishments.establishments.show',
            ],
            /*      [
                'name' => 'devices',
                'url' => 'devices',
                'permission' => '',
            ],*/
            [
                'name' => 'tables',
                'url' => 'table',
                'permission' => '',
                'subMenu' => [
                    [
                        'name' => 'tables',
                        'url' => 'table',
                        'permission' => 'setting.tables.show',
                    ],
                    [
                        'name' => 'areas',
                        'url' => 'area',
                        'permission' => 'establishments.establishments.show',
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
                ],

            ],

        ]

    ],
];