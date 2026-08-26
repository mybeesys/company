<?php

return [

    /**
     * Zatca Phase 2
     *
     * we will consider some config data for zatca v2 in this file.
     * the mode of zatca app is by default the same as app environment.
     */
    'portals'       => [
        'local'         => env('ZATCA_LOCAL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal'),
        'simulation'    => env('ZATCA_SIMULATION', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/simulation'),
        'production'    => env('ZATCA_PRODUCTION', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/core'),
    ],
    'app' => [
        'environment'   => env('ZATCA_ENVIRONMENT', env('APP_ENV', 'local')), # local|simulation|production
        'key'           => env('ZATCA_APP_KEY'),
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
