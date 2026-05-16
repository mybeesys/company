<?php

return [
    'name' => 'Expense',

    /**
     * GL codes on tenant chart (MyBee default tree uses short codes e.g. 517 expenses, 214 VAT).
     * Override via .env if your tenant chart differs.
     */
    'default_expense_gl_code' => env('EXPENSE_DEFAULT_DEBIT_GL', '517'),
    'default_vat_gl_code' => env('EXPENSE_VAT_GL', '214'),

    /**
     * Optional filter: if set and matches existing leaf asset accounts, only those GL codes are listed.
     * Otherwise all active leaf accounts with account_primary_type = asset are shown (from your chart).
     */
    'treasury_gl_codes' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'EXPENSE_TREASURY_GL_CODES',
        '111,112,113,114,115,121,122,123,124,125'
    ))))),
];
