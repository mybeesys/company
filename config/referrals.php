<?php

return [
    // Must be HTTPS in production — HTTP handoff URLs are blocked inside HTTPS tenant iframes.
    'central_app_url' => rtrim(env('CENTRAL_APP_URL', env('APP_URL', 'https://mybeesystem.net')), '/'),
    'device_cookie' => 'mb_device_id',
    'handoff_ttl_minutes' => (int) env('SUBSCRIBE_HANDOFF_TTL_MINUTES', 5),
];
