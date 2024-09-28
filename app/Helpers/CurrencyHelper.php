<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

class CurrencyHelper
{
    public static function format_currency($amount)
    {
        $currencySymbol =  Session::get('currency');

        if (app()->getLocale() == 'ar') {
            $defulteCurrency = ' ريال ';
        } else {
            $defulteCurrency = ' SAR ';
        }

        return $currencySymbol ?? $defulteCurrency  . number_format($amount, 2);
    }


    public static function get_format_currency()
    {
        $currencySymbol =  Session::get('currency');

        if (app()->getLocale() == 'ar') {
            $defulteCurrency = 'ريال';
        } else {
            $defulteCurrency = 'SAR';
        }

        return $currencySymbol ?? $defulteCurrency;
    }
}
