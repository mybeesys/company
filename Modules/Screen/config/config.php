<?php

return [
    'name' => 'Screen',

    /** مدة صلاحية توكن واجهة برمجة الشاشات (Sanctum) بالأيام؛ 0 = بدون انتهاء */
    'api_token_ttl_days' => (int) env('SCREEN_API_TOKEN_TTL_DAYS', 365),
];
