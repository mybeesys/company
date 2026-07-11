<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company API token lifetime (minutes)
    |--------------------------------------------------------------------------
    |
    | null or 0 = no expiration (recommended for Socket.IO clients).
    |
    */
    'token_expiration_minutes' => env('COMPANY_API_TOKEN_EXPIRATION_MINUTES'),

    /*
    |--------------------------------------------------------------------------
    | Revoke previous token on login
    |--------------------------------------------------------------------------
    |
    | When false, each login issues a new token without invalidating older ones.
    | This allows the same account to stay connected from waiter, kitchen, POS, etc.
    |
    */
    'revoke_previous_on_login' => (bool) env('COMPANY_API_REVOKE_PREVIOUS', false),

    /*
    |--------------------------------------------------------------------------
    | Socket verify cache (milliseconds) — used by socket-server .env
    |--------------------------------------------------------------------------
    */
    'socket_verify_cache_ms' => (int) env('SOCKET_VERIFY_CACHE_MS', 86_400_000),
];
