<?php

return [
    'enabled' => env('EMPLOYEE_ACTIVITY_LOG_ENABLED', true),

    /**
     * Path prefixes (after leading slash) skipped entirely.
     */
    'excluded_path_prefixes' => [
        'build/',
        'vendor/',
        'storage/',
        'livewire/',
        '_debugbar/',
        'broadcasting/',
        'sanctum/',
        'up',
    ],

    /**
     * Authenticated GET requests matching these prefixes are not logged (high-frequency polling).
     */
    'excluded_get_prefixes' => [
        'dashboard-v2/api/',
    ],

    /**
     * Request input keys removed before storing in properties.
     */
    'redact_input_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        '_token',
        'remember',
    ],

    /**
     * Routes whose names match these patterns are not logged (regex).
     */
    'excluded_route_patterns' => [
        '/^debugbar\./',
        '/^livewire\./',
        '/^activity\./',
        '/^login\./',
        '/^logout$/',
        '/^auth\.tenant-switch$/',
        '/^password\./',
    ],
];
