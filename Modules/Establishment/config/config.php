<?php

return [
    'name' => 'Establishment',

    /*
    | Temporarily disable payment-method fees (UI + API + web sell).
    | Existing fee rows stay in the database; set true to re-enable.
    */
    'payment_method_fees_enabled' => (bool) env('CASHIER_PAYMENT_METHOD_FEES_ENABLED', false),
];
