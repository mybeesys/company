<?php

namespace Modules\Sales\Utils;

use Modules\General\Models\Transaction;

class SalesUtile
{

    public static function paymentTerms()
    {
        return [
            '0' => __('sales::lang.terms.0'),

            '30' => __('sales::lang.terms.30'),
            '10' => __('sales::lang.terms.10'),
            '6' => __('sales::lang.terms.6'),
            '7' => __('sales::lang.terms.7'),
            '9' => __('sales::lang.terms.9'),
        ];
    }

    public static function paymentMethods()
    {
        return [
            'prepaid' => __('sales::lang.payment_methods.prepaid'),
            'cash' => __('sales::lang.payment_methods.cash'),
            'card' => __('sales::lang.payment_methods.card'),
            'bank_check' => __('sales::lang.payment_methods.bank_check'),
            'bank_transfer' => __('sales::lang.payment_methods.bank_transfer'),
        ];
    }

    public static function orderStatuses()
    {
        return [
            'requested' => __('sales::lang.order_status.requested'),
            'filled' => __('sales::lang.order_status.filled'),
            'shipped' => __('sales::lang.order_status.shipped'),
            'delivered' => __('sales::lang.order_status.delivered'),
            'canceled' => __('sales::lang.order_status.canceled'),
        ];
    }



    public static function generateReferenceNumber($type)
    {
        $currentYear = date('Y');

        $transaction = Transaction::where('type', $type)
            ->whereYear('created_at', $currentYear)
            ->latest()
            ->first();
        $prefx = 'INV-';

        if ($type == 'quotation') {
            $prefx = 'QTN-';
        }
        if ($transaction) {


            $last_ref_no = $transaction->ref_no;

            list(, $yearAndNumber) = explode('-', $last_ref_no);
            list($year, $number) = explode('/', $yearAndNumber);

            if ($year == $currentYear) {
                $newNumber = str_pad($number + 1, 4, '0', STR_PAD_LEFT);
                $new_ref_no = $prefx . $currentYear . '/' . $newNumber;
            } else {
                $new_ref_no = $prefx . $currentYear . '/0001';
            }
        } else {
            $new_ref_no = $prefx . $currentYear . '/0001';
        }

        return $new_ref_no;
    }
}
