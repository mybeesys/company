<?php

return [
    'broadcast_url' => env('SOCKET_BROADCAST_URL', 'http://127.0.0.1:3001/broadcast'),
    'internal_secret' => env('SOCKET_INTERNAL_SECRET', ''),
    'schema_version' => 1,
];
