<?php

namespace Modules\Report\Utils;

use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;

class TransactionUtile
{

    public function getProfitLossDetails()
    {

        $purchase_details = $this->getPurchaseTotals();


        $sell_details = $this->getSellTotals();

        $transaction_types = [
            'purchases-return',
            'sell-return',
            'purchases',
            'sell',
        ];

        $transaction_totals = $this->getTransactionTotals($transaction_types);

        $gross_profit = $this->getGrossProfit();


        //Discounts
        $total_purchase_discount = $transaction_totals['total_purchase_discount'];
        $total_sell_discount = $transaction_totals['total_sell_discount'];
        $total_sell_return_discount = $transaction_totals['total_sell_return_discount'];

        //Purchase
        $data['total_purchase'] = !empty($purchase_details['total_purchase_exc_tax']) ? $purchase_details['total_purchase_exc_tax'] : 0;
        $data['total_purchase_discount'] = !empty($total_purchase_discount) ? $total_purchase_discount : 0;
        $data['total_purchase_return'] = $transaction_totals['total_purchase_return_exc_tax'];

        //Sales
        $data['total_sell'] = !empty($sell_details['total_sell_exc_tax']) ? $sell_details['total_sell_exc_tax'] : 0;
        $data['total_sell_discount'] = !empty($total_sell_discount) ? $total_sell_discount : 0;
        $data['total_sell_return_discount'] = !empty($total_sell_return_discount) ? $total_sell_return_discount : 0;
        $data['total_sell_return'] = $transaction_totals['total_sell_return_exc_tax'];



        $data['gross_profit'] = $gross_profit;
        $data['net_profit'] =  $gross_profit - ($data['total_purchase_discount'] +  $data['total_sell_return_discount'] + $data['total_sell_discount']);



        return $data;
    }

    public function getPurchaseTotals($start_date = null, $end_date = null, $user_id = null)
    {
        $query = Transaction::where('type', 'purchases')
            ->select(
                DB::raw('SUM(final_total) as final_total_sum'),
                DB::raw('SUM((SELECT COALESCE(SUM(tp.amount), 0) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid'),
                DB::raw('SUM(total_before_tax) as total_before_tax_sum'),
            );


        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date);
        }

        if (empty($start_date) && !empty($end_date)) {
            $query->whereDate('transaction_date', '<=', $end_date);
        }

        //Filter by the location
        if (!empty($user_id)) {
            $query->where('transactions.created_by', $user_id);
        }

        $purchase_details = $query->first();

        $output['total_purchase_inc_tax'] = $purchase_details->final_total_sum;
        $output['total_purchase_exc_tax'] = $purchase_details->total_before_tax_sum;
        $output['purchase_due'] = $purchase_details->final_total_sum -
            $purchase_details->total_paid;

        return $output;
    }

    public function getSellTotals($start_date = null, $end_date = null, $created_by = null)
    {
        $query = Transaction::where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select(
                DB::raw('SUM(final_total) as total_sell'),
                DB::raw('SUM(final_total - tax_amount) as total_exc_tax'),
                DB::raw('SUM(final_total - (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)), 0) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) )  as total_due'),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
            );


        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)
                ->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        if (empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        if (!empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }

        $sell_details = $query->first();

        $output['total_sell_inc_tax'] = $sell_details->total_sell;
        $output['total_sell_exc_tax'] = $sell_details->total_before_tax;
        $output['invoice_due'] = $sell_details->total_due;

        return $output;
    }


    public function getTransactionTotals(
        $transaction_types,
        $start_date = null,
        $end_date = null,
        $created_by = null
    ) {
        $query = Transaction::query();

        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)
                ->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        if (empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        //Filter by created_by
        if (!empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }

        if (in_array('purchases-return', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='purchases-return', final_total, 0)) as total_purchase_return_inc_tax"),
                DB::raw("SUM(IF(transactions.type='purchases-return', total_before_tax, 0)) as total_purchase_return_exc_tax")
            );
        }

        if (in_array('sell-return', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell-return', final_total, 0)) as total_sell_return_inc_tax"),
                DB::raw("SUM(IF(transactions.type='sell-return', total_before_tax, 0)) as total_sell_return_exc_tax"),
                DB::raw("SUM(IF(transactions.type='sell-return', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_sell_return_discount")
            );
        }



        if (in_array('purchases', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='purchases', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_purchase_discount")
            );
        }

        if (in_array('sell', $transaction_types)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.status='final', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_sell_discount"),
                // DB::raw("SUM(IF(transactions.type='sell' AND transactions.status='final', rp_redeemed_amount, 0)) as total_reward_amount"),
                // DB::raw("SUM(IF(transactions.type='sell' AND transactions.status='final', round_off_amount, 0)) as total_sell_round_off")
            );
        }

        $transaction_totals = $query->first();
        $output = [];

        if (in_array('purchases-return', $transaction_types)) {
            $output['total_purchase_return_inc_tax'] = !empty($transaction_totals->total_purchase_return_inc_tax) ?
                $transaction_totals->total_purchase_return_inc_tax : 0;

            $output['total_purchase_return_exc_tax'] =
                !empty($transaction_totals->total_purchase_return_exc_tax) ?
                $transaction_totals->total_purchase_return_exc_tax : 0;
        }

        if (in_array('sell-return', $transaction_types)) {
            $output['total_sell_return_inc_tax'] =
                !empty($transaction_totals->total_sell_return_inc_tax) ?
                $transaction_totals->total_sell_return_inc_tax : 0;

            $output['total_sell_return_exc_tax'] =
                !empty($transaction_totals->total_sell_return_exc_tax) ?
                $transaction_totals->total_sell_return_exc_tax : 0;

            $output['total_sell_return_discount'] =
                !empty($transaction_totals->total_sell_return_discount) ?
                $transaction_totals->total_sell_return_discount : 0;
        }



        if (in_array('purchases', $transaction_types)) {
            $output['total_purchase_discount'] =
                !empty($transaction_totals->total_purchase_discount) ?
                $transaction_totals->total_purchase_discount : 0;
        }

        if (in_array('sell', $transaction_types)) {
            $output['total_sell_discount'] =
                !empty($transaction_totals->total_sell_discount) ?
                $transaction_totals->total_sell_discount : 0;
        }

        return $output;
    }

    public function getGrossProfit($start_date = null, $end_date = null, $user_id = null)
    {
        $query = TransactionSellLine::join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftJoin('transactione_purchases_lines as TPL', function ($join) {
                $join->on('transaction_sell_lines.product_id', '=', 'TPL.product_id')
                    ->on('transaction_sell_lines.transaction_id', '=', 'TPL.transaction_id');
            })
            ->join('product_products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'final');

        $query->select(
            DB::raw("
            SUM(
                (transaction_sell_lines.qyt - COALESCE(TPL.qyt, 0)) * transaction_sell_lines.unit_price_inc_tax
            ) AS total,

            SUM(
                (transaction_sell_lines.qyt - COALESCE(TPL.qyt, 0)) *
                (transaction_sell_lines.unit_price_inc_tax - COALESCE(TPL.unit_price_inc_tax, 0))
            ) AS gross_profit
        ")
        );

        if (!empty($start_date) && !empty($end_date) && $start_date != $end_date) {
            $query->whereDate('sale.transaction_date', '>=', $start_date)
                ->whereDate('sale.transaction_date', '<=', $end_date);
        }
        if (!empty($start_date) && !empty($end_date) && $start_date == $end_date) {
            $query->whereDate('sale.transaction_date', $end_date);
        }


        if (!empty($user_id)) {
            $query->where('sale.created_by', $user_id);
        }

        $gross_profit_obj = $query->first();

        $gross_profit = !empty($gross_profit_obj->gross_profit) ? $gross_profit_obj->gross_profit : 0;

        //KNOWS ISSUE: If products are returned then also the discount gets applied for it.

        return $gross_profit;
    }
}