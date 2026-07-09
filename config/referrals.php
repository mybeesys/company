<?php

return [
    'central_app_url' => rtrim(env('CENTRAL_APP_URL', env('APP_URL', 'http://localhost')), '/'),
    'device_cookie' => 'mb_device_id',
];
