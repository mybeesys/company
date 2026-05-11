<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal / licensed product name (full)
    |--------------------------------------------------------------------------
    | يُعرض في التقارير الرسمية، PDF، عقود، وحقوق النشر عند الحاجة لنص كامل.
    | Defaults to APP_NAME when APP_LEGAL_NAME is not set.
    */
    'legal_name' => env('APP_LEGAL_NAME') ?: env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Short brand (UI): tab titles, header, login, footers
    |--------------------------------------------------------------------------
    */
    'short_name_ar' => env('APP_BRAND_SHORT_AR', 'النظم المتكامل'),
    'short_name_en' => env('APP_BRAND_SHORT_EN', 'Integrated Systems'),

];
