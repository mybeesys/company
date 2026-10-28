<?php

return [
    'broadcast_url' => env('SOCKET_BROADCAST_URL', 'http://127.0.0.1:3001/broadcast'),
    'internal_secret' => env('SOCKET_INTERNAL_SECRET', ''),
    'schema_version' => 1,

    /** Public Socket.IO path (Nginx → Node). */
    'client_path' => env('SOCKET_CLIENT_PATH', '/socket.io/'),

    /** Transports suggested to mobile clients. */
    'client_transports' => ['websocket', 'polling'],

    /**
     * When true, non-standard HTTP ports on the current request are echoed in socket_port.
     * Keep false in production (443 only via reverse proxy).
     */
    'expose_dev_port' => (bool) env('SOCKET_EXPOSE_DEV_PORT', false),
];
