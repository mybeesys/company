<?php

return [

    /**
     * Zatca Phase 2
     *
     * Environment + production app key are owned by .env (not the settings UI)
     * so operators cannot accidentally flip sandbox ↔ production.
     *
     * Package trees:
     * - local|simulation → packages/fatoora-zatca (sandbox build)
     * - production       → packages/fatoora-zatca-production (licensed production build)
     *
     * Do NOT fall back to APP_ENV — a production Laravel deploy may still be on ZATCA sandbox.
     */
    'portals'       => [
        'local'         => env('ZATCA_LOCAL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal'),
        'simulation'    => env('ZATCA_SIMULATION', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation'),
        'production'    => env('ZATCA_PRODUCTION', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core'),
    ],
    'app' => [
        'environment'   => env('ZATCA_ENVIRONMENT', 'local'), // local|simulation|production
        'key'           => env('ZATCA_APP_KEY'),
        // When true: UI shows env/key as read-only; saves always use .env values.
        'lock_connection_from_env' => filter_var(env('ZATCA_LOCK_CONNECTION_FROM_ENV', true), FILTER_VALIDATE_BOOLEAN),
    ],
    'packages' => [
        'sandbox' => base_path('packages/fatoora-zatca'),
        'production' => base_path('packages/fatoora-zatca-production'),
    ],

    /**
     * Show ZATCA Phase 2 settings in the admin sidebar (and settings cards).
     * Set ZATCA_SHOW_IN_MENU=false in .env to hide the link without removing the module.
     */
    'show_in_menu' => filter_var(env('ZATCA_SHOW_IN_MENU', true), FILTER_VALIDATE_BOOLEAN),

    /**
     * Soft brand accent for ZATCA PDF (hex). Defaults to Metronic --bs-primary.
     */
    'pdf_primary_color' => env('ZATCA_PDF_PRIMARY', '#e9b71f'),

    /**
     * Operations rules UI/effect toggles (temporary hide without removing the feature).
     * When false: field is hidden and has no effect on sell create/save.
     */
    'ops_rules' => [
        'disable_order_tax' => filter_var(env('ZATCA_OPS_DISABLE_ORDER_TAX', false), FILTER_VALIDATE_BOOLEAN),
        'default_sales_discount' => filter_var(env('ZATCA_OPS_DEFAULT_SALES_DISCOUNT', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'exemptions' => [
        'Z' => [
            'code' => 'VATEX-SA-32',
            'reason' => 'Export of goods',
        ],
        'E' => [
            'code' => 'VATEX-SA-29-7',
            'reason' => 'Financial services mentioned in Article 29 of the VAT Regulations',
        ],
        'O' => [
            'code' => 'VATEX-SA-OOS',
            'reason' => 'Exempt',
        ],
    ],

];
