<?php

return [
    'name' => 'Screen',

    /** مدة صلاحية توكن واجهة برمجة الشاشات (Sanctum) بالأيام؛ 0 = بدون انتهاء */
    'api_token_ttl_days' => (int) env('SCREEN_API_TOKEN_TTL_DAYS', 365),

    /** WebSocket — مسار الاشتراك (تطبيق الشاشة) */
    'pairing_channel_prefix' => 'screen.pairing.',
    'device_channel_prefix' => 'screen.device.',
    'pairing_linked_event' => 'screen.linked',
    'device_unlinked_event' => 'screen.unlinked',
    'playlist_updated_event' => 'screen.playlist.updated',
    'websocket_path' => env('SCREEN_WEBSOCKET_PATH', '/ws'),

    /** مدة صلاحية PIN الربط المؤقت بالثواني (افتراضي 120 = دقيقتان) */
    'pairing_pin_ttl_seconds' => (int) env('SCREEN_PAIRING_PIN_TTL_SECONDS', 120),

    /** طول PIN الربط (أرقام فقط) */
    'pairing_pin_length' => (int) env('SCREEN_PAIRING_PIN_LENGTH', 6),
];
