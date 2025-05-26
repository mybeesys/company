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
                'permission' => 'products.product.show',

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
                'permission' => '',

            ],
            [
                'name' => 'serviceFee',
                'url' => 'serviceFee',
                'permission' => '',

            ],
            [
                'name' => 'type-service',
                'url' => 'type-service',
                'permission' => '',

            ],


            [
                'name' => 'priceTier',
                'url' => 'priceTier',
                'permission' => '',

            ],
            [
                'name' => 'discount',
                'url' => 'discount',
                'permission' => '',

            ],
            // [
            //     'name' => 'linkedCombo',
            //     'url' => 'linkedCombo',
            //     'permission' => '',

            // ],
            [
                'name' => 'productBarcode',
                'url' => 'productBarcode/barcode',
                'permission' => '',

            ],
            [
                'name' => 'import',
                'url' => 'importProduct/import',
                'permission' => '',

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
                'permission' => 'inventory.rma.show',
            ],
            [
                'name' => 'waste',
                'url' => 'waste',
                'permission' => 'inventory.waste.show',
            ],
            [
                'name' => 'import',
                'url' => 'openInventoryImport/import',
                'permission' => 'inventory.transfer.show',
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
                'name' => 'quotations',
                'url' => 'quotations',
                'permission' => '',
            ],

            [
                'name' => 'invoices',
                'url' => 'invoices',
                'permission' => '',
            ],
            [
                'name' => 'sell-return',
                'url' => 'sell-return',
                'permission' => '',
            ],
            [
                'name' => 'customer_receipts',
                'url' => 'receipts',
                'permission' => '',
            ],
            [
                'name' => 'coupons',
                'url' => 'coupon',
                'permission' => ''
            ]
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
                'name' => 'purchase-order',
                'url' => 'purchases-order',
                'permission' => '',
            ],
            [
                'name' => 'purchase_invoices',
                'url' => 'purchase-invoices',
                'permission' => '',
            ],
            [
                'name' => 'purchases-return',
                'url' => 'purchases-return',
                'permission' => '',
            ],
            [
                'name' => 'supplier_receipts',
                'url' => 'suppliers-receipts',
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
                'name' => 'accounts-routing',
                'url' => 'accounts-routing',
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
            [
                'name' => 'receipt_vouchers',
                'url' => 'receipt-vouchers',
                'permission' => '',
            ],
            [
                'name' => 'payment_vouchers',
                'url' => 'payment-vouchers',
                'permission' => '',
            ],

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
                'permission' => '',
            ],
            [
                'name' => 'income-statement',
                'url' => 'income-statement',
                'permission' => '',
            ],
            [
                'name' => 'ledger',
                'url' => 'ledger',
                'permission' => '',
            ],

            [
                'name' => 'balance_sheet',
                'url' => 'balance-sheet',
                'permission' => '',
            ],
            [
                'name' => 'journal-report',
                'url' => 'journal-report',
                'permission' => '',
            ],
            [
                'name' => 'cash-flow',
                'url' => 'cash-flow',
                'permission' => '',
            ],

            [
                'name' => 'customers-suppliers-statement',
                'url' => 'customers-suppliers-statement',
                'permission' => '',
            ],


            [
                'name' => 'account-receivable-ageing-report',
                'url' => 'account-receivable-ageing-report',
                'permission' => '',
            ],
            [
                'name' => 'account-receivable-ageing-details',
                'url' => 'account-receivable-ageing-details',
                'permission' => '',
            ],
            [
                'name' => 'account-payable-ageing-report',
                'url' => 'account-payable-ageing-report',
                'permission' => '',
            ],

            [
                'name' => 'account-payable-ageing-details',
                'url' => 'account-payable-ageing-details',
                'permission' => '',
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
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'clients_suppliers_settings',
                'url' => 'client-supplier-setting',
                'permission' => ''
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
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'main',
                'url' => 'main',
                'permission' => '',
            ],
        ]
    ],
    [
        'name' => 'reports_module',
        'url' => '/',
        'icon' => 'fas fa-bar-chart',
        'permission' => '',
        'subMenu' => [

            [
                'name' => 'Profit-Loss',
                'url' => 'Profit-Loss',
                'permission' => '',
            ],
            [
                'name' => 'sales',
                'url' => 'sales-report',
                'permission' => '',
            ],
            [
                'name' => 'purchase-payment-report',
                'url' => 'purchase-payment-report',
                'permission' => '',
            ],

            [
                'name' => 'sell-payment-report',
                'url' => 'sell-payment-report',
                'permission' => '',
            ],
            [
                'name' => 'purchase-sell',
                'url' => 'purchase-sell',
                'permission' => '',
            ],

            [
                'name' => 'product-sales-report',
                'url' => 'product-sales-report',
                'permission' => '',
            ],

            [
                'name' => 'product-purchase-report',
                'url' => 'product-purchase-report',
                'permission' => '',
            ],

            [
                'name' => 'purchase-payment-report',
                'url' => 'purchase-payment-report',
                'permission' => '',
            ],

            [
                'name' => 'sell-payment-report',
                'url' => 'sell-payment-report',
                'permission' => '',
            ],
            [
                'name' => 'product-inventory-report',
                'url' => 'product-inventory-report',
                'permission' => '',
            ],




        ]
    ],
    [
        'name' => 'setting',
        'url' => '',
        'icon' => 'fas fa-cog',
        'permission' => '',
        'subMenu' => [
            [
                'name' => 'general_setting',
                'url' => 'general-setting',
                'permission' => '',

            ],
            [
                'name' => 'establishments',
                'url' => 'establishment',
                'permission' => 'establishments.establishments.show',
            ],
            [
                'name' => 'devices',
                'url' => 'devices',
                'permission' => '',
            ],
            [
                'name' => 'tables',
                'url' => 'table',
                'permission' => '',
                'subMenu' => [
                    [
                        'name' => 'tables',
                        'url' => 'table',
                        'permission' => '',
                    ],
                    [
                        'name' => 'areas',
                        'url' => 'area',
                        'permission' => 'establishments.establishments.show',
                    ],
                    [
                        'name' => 'tables_qr',
                        'url' => 'areaQR',
                        'permission' => '',
                    ],
                    [
                        'name' => 'menu_qr',
                        'url' => 'menuQR',
                        'permission' => '',
                    ],
                ],

            ],

        ]

    ],
];
