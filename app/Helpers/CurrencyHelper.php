<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Modules\General\Models\Setting;

class CurrencyHelper
{
    /** Official Saudi Riyal sign (Unicode 17.0, U+20C1). */
    public const SAR_SIGN = "\u{20C1}";

    public static function normalizeCurrencyDisplay(?string $stored): string
    {
        $v = trim((string) $stored);
        if ($v === '' || strcasecmp($v, 'SAR') === 0 || $v === 'ر.س' || $v === 'ر/س' || strcasecmp($v, 'sr') === 0) {
            return self::SAR_SIGN;
        }

        return $v;
    }

    public static function format_currency($amount)
    {
        $currencySymbol = Setting::where('key', 'currency')->value('value');

        return number_format((float) $amount, 2) . '  ' . self::normalizeCurrencyDisplay($currencySymbol);
    }

    public static function get_format_currency()
    {
        $currencySymbol = Setting::where('key', 'currency')->value('value');

        return self::normalizeCurrencyDisplay($currencySymbol);
    }
}
