<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Session;

class TaxHelper
{
    public static function getTax($amount, $tax_rate)
    {
        if ($amount < 0 || $tax_rate < 0) {
            return 0;
        }
 
        $tax_amount = $amount * ($tax_rate / 100);

        return round($tax_amount, 2);
    }

}