<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default defaults for ZATCA settings form (optional)
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'country_code' => 'SA',
        'invoice_type' => '1100',
        'environment' => env('ZATCA_ENVIRONMENT', 'local'),
    ],
];
