<?php

/**
 * Tenant-side entitlement map.
 * Keys must match E:/clients/config/entitlements.php module keys.
 * Gating is soft (menu/API) — nwidart modules stay loaded to avoid class fatals.
 *
 * Matching note: EnsureModuleEntitlement uses the longest matching pattern so
 * specific paths (e.g. purchaseOrder, sales-report) win over short prefixes.
 *
 * Accounting split:
 * - Sellable UI: chart CRUD, journals UI, vouchers, cost centers, periodic UI,
 *   accounting reports/settings/dashboard → gate with `accounting`.
 * - Ledger ENGINE (posting from sales/purchases/inventory/expenses/receipts):
 *   stays available in-process via AccountingUtil/models. Shared pickers like
 *   accounts-dropdown must NOT require the accounting commercial module.
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
        'inventory' => 'inventory',
        'prep' => 'inventory',
        'transfer' => 'inventory',
        'waste' => 'inventory',
        'import' => 'inventory',
        'sales' => 'sales',
        'quotations' => 'sales',
        'invoices' => 'sales',
        'sell-return' => 'sales',
        'clients' => ['sales', 'purchases'],
        'customer_receipts' => 'sales',
        'coupons' => 'sales',
        'purchases' => 'purchases',
        'suppliers' => 'purchases',
        'purchase-order' => 'purchases',
        'purchase_invoices' => 'purchases',
        'purchases-return' => 'purchases',
        'supplier_receipts' => 'purchases',
        'accounting_module' => 'accounting',
        'chart_of_accounts' => 'accounting',
        'journalEntry' => 'accounting',
        'receipt_vouchers' => 'accounting',
        'payment_vouchers' => 'accounting',
        'costCenter' => 'accounting',
        'periodic' => 'accounting',
        'accounting_settings' => 'accounting',
        'accounting_reports' => 'accounting',
        'clients_suppliers_module' => ['sales', 'purchases'],
        'clients_suppliers_settings' => ['sales', 'purchases'],
        'screen_module' => 'digital_screens',
        'devices' => 'digital_screens',
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
     * Route name / path prefixes => required module.
     * Prefer specific prefixes; longest match wins at runtime.
     */
    'route_entitlements' => [
        // Inventory (before broad purchase* patterns)
        'inventory.' => 'inventory',
        'productInventory' => 'inventory',
        'ingredientInventory' => 'inventory',
        'purchaseOrder' => 'inventory',
        'purchaseOrderReport' => 'inventory',
        'inventoryOperation' => 'inventory',
        'openInventoryImport' => 'inventory',
        'prepareRecipe' => 'inventory',
        'needPreparationList' => 'inventory',
        'getIngredientList' => 'inventory',
        'getProductInventory' => 'inventory',
        'listTransactions' => 'inventory',
        'establishmentList' => 'inventory',
        'updateRecive' => 'inventory',
        'warehouselist' => 'inventory',
        'warehouse' => 'inventory',
        'transfer' => 'inventory',
        'waste' => 'inventory',
        'storeWaste' => 'inventory',
        'wasteList' => 'inventory',
        'rma' => 'inventory',
        'prep' => 'inventory',

        // Sales
        'sales.' => 'sales',
        'sales-dashbord' => 'sales',
        'sales-favorites' => 'sales',
        'create-invoice' => 'sales',
        'convert-to-invoice' => 'sales',
        'store-invoice' => 'sales',
        'products-for-sale' => 'sales',
        'products-for-quotation' => 'sales',
        'products-for-client' => 'sales',
        'sell-return' => 'sales',
        'create-sell-return' => 'sales',
        'store-sell-return' => 'sales',
        'create-quotation' => 'sales',
        'store-quotation' => 'sales',
        'invoices' => 'sales',
        'quotations' => 'sales',
        'receipts' => 'sales',
        'create-receipts' => 'sales',
        // Shared store/edit endpoints — controller enforces sales vs purchases by contact/type.
        'store-receipts' => ['sales', 'purchases'],
        'receipts-payments' => ['sales', 'purchases'],
        'show-receipts-payments' => ['sales', 'purchases'],
        'show-receipts-payments-export-pdf' => ['sales', 'purchases'],
        'get-transactions' => 'sales',
        'coupons.' => 'sales',
        'coupon' => 'sales',
        'getCustomers' => 'sales',
        'getSuppliers' => 'purchases',

        // Purchases (avoid bare "purchase" — collisions with inventory/reports)
        'purchases.' => 'purchases',
        'purchases-' => 'purchases',
        'purchase-dashbord' => 'purchases',
        'purchase-order' => 'purchases',
        'purchase_invoices' => 'purchases',
        'purchase-invoices' => 'purchases',
        'create-purchases' => 'purchases',
        'store-purchases' => 'purchases',
        'create-purchase-order' => 'purchases',
        'store-purchase-order' => 'purchases',
        'convert-po-to-invoice' => 'purchases',
        'suppliers-receipts' => 'purchases',
        'create-suppliers-receipts' => 'purchases',

        // Clients hub (soft-OR). Suppliers are purchases-only (matches menu).
        'clients' => ['sales', 'purchases'],
        'suppliers' => 'purchases',
        'client-' => ['sales', 'purchases'],
        'supplier-' => 'purchases',
        'client-supplier-setting' => ['sales', 'purchases'],
        'store-loyalty-point-settings' => ['sales', 'purchases'],

        // Settings mutations (UI tabs are soft-gated; these close direct POST/GET)
        'update-inventory-policy' => 'inventory',
        'update-inventory-costing-method' => 'inventory',
        'preview-inventory-costing-rebuild' => 'inventory',
        'rebuild-inventory-costing' => 'inventory',
        'update-reward-points' => ['sales', 'cashier_pos'],
        'invoice-settings-get' => ['sales', 'purchases', 'cashier_pos'],
        'invoice-settings-update' => ['sales', 'purchases', 'cashier_pos'],
        'save-nots-terms' => ['sales', 'purchases', 'cashier_pos'],

        // Accounting UI
        'accounting.' => 'accounting',
        'accounting-' => 'accounting',
        'accounting/' => 'accounting',
        'tree-of-accounts' => 'accounting',
        // Accounting UI only (sellable). Ledger ENGINE posting stays in-process via
        // AccountingUtil / models and must NOT be HTTP-gated here.
        'create-account' => 'accounting',
        'store-account' => 'accounting',
        'update-account' => 'accounting',
        'store-sub-account' => 'accounting',
        'create-default-accounts' => 'accounting',
        'ledger' => 'accounting',
        'print-ledger' => 'accounting',
        'ledger-export' => 'accounting',
        'change-status-account' => 'accounting',
        'next-gl-code' => 'accounting',
        'delete-account' => 'accounting',
        // Intentionally NOT gated: accounts-dropdown — shared picker for sales/purchases/
        // clients/expenses/payment-methods (engine support surface).
        'accounts-routing' => 'accounting',
        'journal-entry' => 'accounting',
        'opening-balance' => 'accounting',
        'cost-center' => 'accounting',
        'change-status-cost-center' => 'accounting',
        'payment-vouchers' => 'accounting',
        'receipt-vouchers' => 'accounting',
        'income-statement' => 'accounting',
        'trial-balance' => 'accounting',
        'balance-sheet' => 'accounting',
        'journal-report' => 'accounting',
        'expense-report' => 'accounting',
        'cash-flow' => 'accounting',
        'customers-suppliers-statement' => 'accounting',
        'account-receivable-ageing' => 'accounting',
        'account-payable-ageing' => 'accounting',
        'track-action' => 'accounting',
        'inventory/periodic-inventory' => 'accounting',
        'periodic-inventory' => 'accounting',
        'get-products-by-establishment' => 'accounting',

        // Expenses
        'expenses.' => 'expenses',

        // Franchise
        'franchise.' => 'franchise',
        'franchise/' => 'franchise',

        // Digital screens (avoid bare paths like "main"/"device" — those collide with other modules)
        'screen.' => 'digital_screens',
        'screens.' => 'digital_screens',
        'promos.' => 'digital_screens',
        'playlists.' => 'digital_screens',
        'devices.' => 'digital_screens',
        'device/' => 'digital_screens',
        'promo/' => 'digital_screens',
        'playlist/' => 'digital_screens',

        // Electronic menu / POS shared surfaces
        'menuQR' => 'electronic_menu',
        'menu-feedback' => 'electronic_menu',
        'menu-qr' => 'electronic_menu',
        'areaQR' => 'electronic_menu',
        'reservation.menu' => 'electronic_menu',
        'reservation.menuQr' => 'electronic_menu',
        'menu/' => 'electronic_menu',
        'menuSimple' => 'electronic_menu',
        'order/products' => 'electronic_menu',
        'order.products' => 'electronic_menu',
        'generate-menu-token' => 'electronic_menu',
        'table' => ['cashier_pos', 'electronic_menu'],
        'area' => ['cashier_pos', 'electronic_menu'],
        'searchAreas' => ['cashier_pos', 'electronic_menu'],

        // Reports hub routes (source module checked separately)
        'payment-reports' => 'reports',
        'sales-report' => 'reports',
        'sales-reports.' => 'reports',
        'product-sales-report' => 'reports',
        'product-purchase-report' => 'reports',
        'purchase-payment-report' => 'reports',
        'purchase-sell' => 'reports',
        'Profit-Loss' => 'reports',
        'Product-Stock-Report' => 'reports',
        'Register-Report' => 'reports',
        'comparison-lookup' => 'reports',
        'reports/get-profit' => 'reports',
        'api/v1/report' => 'reports',
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
        'purchase-subcategories' => 'purchases',
        'purchase-units' => 'purchases',
        'purchase-payment-methods' => 'purchases',
        'product-inventory' => 'inventory',
        'Product-Stock' => 'inventory',
        'product-movement' => 'inventory',
        'Register-Report' => 'cashier_pos',
        'payment-reports' => ['sales', 'purchases', 'cashier_pos', 'inventory'],
    ],

    /**
     * API path prefixes (without leading slash) => required module.
     * Longest matching prefix wins. Unlisted API paths remain allowed (platform/shared).
     */
    'api_entitlements' => [
        'api/admin/v1/screen' => 'digital_screens',
        'api/v1/screen' => 'digital_screens',
        'api/v1/inventory' => 'inventory',
        'api/v1/expense' => 'expenses',
        'api/v1/franchise' => 'franchise',
        'api/v1/report' => 'reports',
        'api/v1/coupons' => 'sales',
        'api/v1/invoices' => 'sales',
        'api/sales-invoices' => 'sales',
        'api/stor-sales-invoice' => 'sales',
        'api/stor-sell-return' => 'sales',
        'api/stor-purchases-return' => 'purchases',
        'api/new-order' => 'cashier_pos',
        'api/kitchen-orders' => 'cashier_pos',
        'api/waiter' => 'cashier_pos',
        'api/tables' => 'cashier_pos',
        'api/get-tables' => 'cashier_pos',
        'api/change-status' => 'cashier_pos',
        'api/cancel-order' => 'cashier_pos',
        'api/establishment-orders' => 'cashier_pos',
        'api/orders' => 'cashier_pos',
        'api/update-orders' => 'cashier_pos',
        'api/types-of-service' => 'cashier_pos',
        'api/update-item-status' => 'cashier_pos',
        'api/update-order-status' => 'cashier_pos',
        'api/order' => 'electronic_menu',
        // Shared ledger pickers (engine support) — soft-OR, not accounting-UI-only.
        'api/accounts' => ['accounting', 'sales', 'purchases', 'expenses', 'cashier_pos', 'inventory'],
        'api/cost-centers' => ['accounting', 'sales', 'purchases', 'expenses', 'cashier_pos'],
        'api/clients' => ['sales', 'purchases'],
        'api/get-suppliers' => 'purchases',
        'api/contact-' => ['sales', 'purchases'],
    ],

    /**
     * General settings page sections (`/general-setting` tabs / nested panes).
     * Key => required entitlement module(s). `platform` is always allowed.
     */
    'settings_sections' => [
        'company_details' => 'platform',
        'general' => 'platform',
        'taxes' => 'platform',
        'payment_methods' => 'platform',
        'notifications' => 'platform',
        'notifications_general' => 'platform',
        'notifications_clients' => ['sales', 'purchases'],
        'notifications_suppliers' => 'purchases',
        'notifications_inventory' => 'inventory',
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
     * Prefix settings row `type` => required module(s).
     * Unlisted types stay visible (platform).
     */
    'settings_prefix_entitlements' => [
        'invoices' => ['sales', 'purchases', 'cashier_pos'],
        'quotations' => 'sales',
        'purchase-order' => 'purchases',
        'journalEntry' => 'accounting',
        'purchase' => 'purchases',
        'sell' => ['sales', 'cashier_pos'],
    ],

    /**
     * Notification template `type` => required module(s).
     * Unlisted types stay under platform notifications.
     */
    'settings_notification_entitlements' => [
        'created_emp' => 'platform',
        'new_sell' => ['sales', 'cashier_pos'],
        'payment_received' => ['sales', 'cashier_pos'],
        'payments' => ['sales', 'cashier_pos'],
        'new_booking' => ['sales', 'cashier_pos', 'electronic_menu'],
        'new_quotation' => 'sales',
        'new_order' => 'purchases',
        'payment_paid' => 'purchases',
        'items_received' => 'purchases',
        'items_pending' => 'purchases',
        'purchase_order' => 'purchases',
        'low_stock_alert_notification' => 'inventory',
    ],

    /**
     * When no company_entitlements row exists (legacy tenants), allow everything.
     * Set false once all tenants are migrated to entitlements.
     */
    'legacy_unrestricted' => true,
];
