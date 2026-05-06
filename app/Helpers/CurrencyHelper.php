<?php

namespace App\Helpers;

class CurrencyHelper
{
    /** Same Saudi Riyal sign used in menuSimple. */
    public const SAR_SIGN = "\u{20C1}";

    public const SAR_TEXT = 'ريال';

    public static function normalizeCurrencyDisplay(?string $stored): string
    {
        $v = trim((string) $stored);
        if (
            $v === '' ||
            strcasecmp($v, 'SAR') === 0 ||
            $v === 'ر.س' ||
            $v === 'ر/س' ||
            strcasecmp($v, 'sr') === 0 ||
            $v === '﷼'
        ) {
            return self::SAR_SIGN;
        }

        return $v;
    }

    public static function format_currency($amount)
    {
        return number_format((float) $amount, 2).' '.self::displayCurrencyUnit();
    }

    public static function get_format_currency()
    {
        return self::displayCurrencyUnit();
    }

    private static function displayCurrencyUnit(): string
    {
        $isMenuRoute = request()->routeIs(
            'reservation.menu',
            'reservation.menuSimple',
            'reservation.menuSimple.feedback',
            'order.products'
        );

        if ($isMenuRoute) {
            return '<span class="currency-symbol">'.self::SAR_SIGN.'</span>';
        }

        return self::SAR_TEXT;
    }
}
