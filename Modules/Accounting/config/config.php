<?php

return [
    'name' => 'Accounting',

    /**
     * Show "Repair GL codes" on tree-of-accounts (and import) screens.
     */
    'show_repair_gl_codes' => (bool) env('ACCOUNTING_SHOW_REPAIR_GL_CODES', false),

    /**
     * Show "Full accounting reset" button on tree-of-accounts. Execution still requires allow_full_reset
     * or APP_ENV local|staging (see AccountingFullResetService).
     */
    'show_full_reset' => (bool) env('ACCOUNTING_SHOW_FULL_RESET', false),

    /**
     * When true, POST /accounting/staging-full-reset may wipe all tenant accounting data
     * (requires auth + confirm=RESET_ACCOUNTING_FULL). Also allowed when APP_ENV is local or staging.
     */
    'allow_full_reset' => (bool) env('ACCOUNTING_ALLOW_FULL_RESET', false),
];
