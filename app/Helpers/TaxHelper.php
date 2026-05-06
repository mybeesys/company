<?php

namespace App\Helpers;

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

    public static function getAmountBeforeTax($amountWithTax, $tax_rate)
    {
        if ($amountWithTax < 0 || $tax_rate < 0) {
            return 0;
        }

        $decimalTaxRate = $tax_rate / 100;
        $originalAmount = $amountWithTax / (1 + $decimalTaxRate);

        return round($originalAmount, 2);
    }
}
