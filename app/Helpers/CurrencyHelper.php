<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;
use Modules\General\Models\Setting;

class CurrencyHelper
{
    public static function format_currency($amount)
    {
        $currencySymbol = Setting::where('key', 'currency')->value('value');
        $defulteCurrency = ' ر.س';


        return    number_format($amount, 2) . '  ' . $currencySymbol;
    }


    public static function get_format_currency()
    {
        $currencySymbol = Setting::where('key', 'currency')->value('value');

        $defulteCurrency = 'ر.س';


        return $currencySymbol ?? $defulteCurrency;
    }
}