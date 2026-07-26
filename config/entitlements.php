<?php

/**
 * Tenant-side entitlement map.
 * Keys must match E:/clients/config/entitlements.php module keys.
 * Gating is soft (menu/API) — nwidart modules stay loaded to avoid class fatals.
 */
return [

    'always_menu_keys' => [
        'dashboard',
        'my_companies',
        'referrals',
        'product_module',
        'employees_management_module',
        'setting',
    ],

    /**
     * Menu item `name` => required entitlement module key.
     * Nested items can override with their own entitlement.
     */
    'menu_entitlements' => [
        'franchise' => 'franchise',
        'inventory_module' => 'inventory',
        'sales' => 'sales',
        'purchases' => 'purchases',
        'accounting_module' => 'accounting',
        'clients_suppliers_module' => ['sales', 'purchases'], // any of
        'screen_module' => 'digital_screens',
        'reports_module' => 'reports',
        'expenses_manage' => 'expenses',
        // Settings / tables surfaces
        'tables' => ['cashier_pos', 'electronic_menu'],
        'areas' => ['cashier_pos', 'electronic_menu'],
        'tables_qr' => 'electronic_menu',
        'menu_qr' => 'electronic_menu',
        'menu_feedback' => 'electronic_menu',
        'pos_roles' => 'cashier_pos',
    ],

    /**
     * Route name prefixes / patterns => required module.
     * Checked by EnsureModuleEntitlement when applied.
     */
    'route_entitlements' => [
        'screen.' => 'digital_screens',
        'franchise.' => 'franchise',
        'inventory.' => 'inventory',
        'productInventory' => 'inventory',
        'prep' => 'inventory',
        'sales.' => 'sales',
        'invoices' => 'sales',
        'quotations' => 'sales',
        'purchases.' => 'purchases',
        'purchase' => 'purchases',
        'accounting.' => 'accounting',
        'tree-of-accounts' => 'accounting',
        'journal-entry' => 'accounting',
        'expenses.' => 'expenses',
        'payment-reports' => 'reports',
        'sales-report' => 'reports',
        'sales-reports.' => 'reports',
        'product-sales-report' => 'reports',
        'product-purchase-report' => 'reports',
        'Product-Stock-Report' => 'reports',
        'Register-Report' => 'reports',
        'menuQR' => 'electronic_menu',
        'menu-feedback' => 'electronic_menu',
        'areaQR' => 'electronic_menu',
        'table' => ['cashier_pos', 'electronic_menu'],
        'area' => ['cashier_pos', 'electronic_menu'],
    ],

    /**
     * After reports entitlement passes, these path/route fragments also need a source module.
     */
    'report_source_entitlements' => [
        'sales-report' => 'sales',
        'product-sales' => 'sales',
        'sell-payment' => 'sales',
        'weekday-sales' => 'sales',
        'sales-comparison' => 'sales',
        'Profit-Loss' => ['sales', 'purchases', 'accounting'],
        'purchase-sell' => ['sales', 'purchases'],
        'product-purchase' => 'purchases',
        'purchase-payment' => 'purchases',
        'product-inventory' => 'inventory',
        'Product-Stock' => 'inventory',
        'product-movement' => 'inventory',
        'Register-Report' => 'cashier_pos',
        'payment-reports' => ['sales', 'purchases', 'cashier_pos'],
    ],

    /**
     * API path prefixes (without leading slash) => required module.
     */
    'api_entitlements' => [
        'api/v1/screen' => 'digital_screens',
        'api/new-order' => 'cashier_pos',
        'api/kitchen-orders' => 'cashier_pos',
        'api/waiter' => 'cashier_pos',
        'api/tables' => 'cashier_pos',
        'api/v1/invoices' => 'sales',
        'api/v1/coupons' => 'sales',
    ],

    /**
     * General settings page sections (`/general-setting` tabs).
     * Key => required entitlement module(s). `platform` is always allowed.
     */
    'settings_sections' => [
        'company_details' => 'platform',
        'general' => 'platform',
        'taxes' => 'platform',
        'payment_methods' => 'platform',
        'notifications' => 'platform',
        'mail_settings' => 'platform',
        'sms_settings' => 'platform',
        'prefix_settings' => 'platform',
        'default_unit' => 'platform',
        'subscription_modules' => 'platform',
        'inventory_policy' => 'inventory',
        'inventory_costing' => 'inventory',
        'sales' => ['sales', 'cashier_pos'],
        'purchases' => 'purchases',
        'invoice_settings' => ['sales', 'purchases', 'cashier_pos'],
        'reward_points' => ['sales', 'cashier_pos'],
    ],

    /**
     * When no company_entitlements row exists (legacy tenants), allow everything.
     * Set false once all tenants are migrated to entitlements.
     */
    'legacy_unrestricted' => true,
];
