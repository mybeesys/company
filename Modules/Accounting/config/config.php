<?php

return [
    'name' => 'Accounting',

    /**
     * When true, POST /accounting/staging-full-reset may wipe all tenant accounting data
     * (requires auth + confirm=RESET_ACCOUNTING_FULL). Also allowed when APP_ENV is local or staging.
     */
    'allow_full_reset' => (bool) env('ACCOUNTING_ALLOW_FULL_RESET', false),
];
