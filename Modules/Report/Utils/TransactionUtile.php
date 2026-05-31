<?php

namespace Modules\Report\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;

class TransactionUtile
{
    /**
     * @param  array<int>|null  $establishment_ids
     * @return array<string, float|int>
     */
    public function getProfitLossDetails(
        ?string $start_date = null,
        ?string $end_date = null,
        ?array $establishment_ids = null
    ): array {
        $purchase_details = $this->getPurchaseTotals($start_date, $end_date, null, $establishment_ids);
        $sell_details = $this->getSellTotals($start_date, $end_date, null, $establishment_ids);

        $transaction_types = [
            'purchases-return',
            'sell-return',
            'purchases',
            'sell',
        ];

        $transaction_totals = $this->getTransactionTotals(
            $transaction_types,
            $start_date,
            $end_date,
            null,
            $establishment_ids
        );

        $gross_profit = $this->getGrossProfit($start_date, $end_date, null, $establishment_ids);

        $total_purchase_discount = $transaction_totals['total_purchase_discount'] ?? 0;
        $total_sell_discount = $transaction_totals['total_sell_discount'] ?? 0;
        $total_sell_return_discount = $transaction_totals['total_sell_return_discount'] ?? 0;

        $total_purchase = (float) ($purchase_details['total_purchase_exc_tax'] ?? 0);
        $total_sell = (float) ($sell_details['total_sell_exc_tax'] ?? 0);
        $total_purchase_return = (float) ($transaction_totals['total_purchase_return_exc_tax'] ?? 0);
        $total_sell_return = (float) ($transaction_totals['total_sell_return_exc_tax'] ?? 0);

        $gross_profit = (float) $gross_profit;
        $net_profit = $gross_profit - (
            $total_purchase_discount + $total_sell_return_discount + $total_sell_discount
        );

        return [
            'total_purchase' => $total_purchase,
            'total_purchase_discount' => (float) $total_purchase_discount,
            'total_purchase_return' => $total_purchase_return,
            'total_sell' => $total_sell,
            'total_sell_discount' => (float) $total_sell_discount,
            'total_sell_return_discount' => (float) $total_sell_return_discount,
            'total_sell_return' => $total_sell_return,
            'gross_profit' => $gross_profit,
            'net_profit' => $net_profit,
            'net_sales' => max(0, $total_sell - $total_sell_return),
            'net_purchases' => max(0, $total_purchase - $total_purchase_return),
        ];
    }

    /**
     * Default profit/loss report period: current calendar year.
     *
     * @return array{start: string, end: string}
     */
    public static function defaultDateRange(): array
    {
        return [
            'start' => now()->startOfYear()->format('Y-m-d'),
            'end' => now()->endOfYear()->format('Y-m-d'),
        ];
    }

    /**
     * @return array{start: ?string, end: ?string}
     */
    public static function parseDateRangeFromRequest(Request $request): array
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');

        if ((! $start || ! $end) && $request->filled('date_range')) {
            $raw = trim((string) $request->input('date_range'));
            $parts = preg_split('/\s*(?:إلى|to)\s*/iu', $raw, 2);
            if (count($parts) === 2) {
                $start = trim($parts[0]);
                $end = trim($parts[1]);
            }
        }

        if ((! $start || ! $end) && ! $request->filled('date_range')) {
            return self::defaultDateRange();
        }

        return [
            'start' => $start ?: null,
            'end' => $end ?: null,
        ];
    }

    /**
     * @param  array<int>|null  $establishment_ids
     */
    public function getPurchaseTotals(
        ?string $start_date = null,
        ?string $end_date = null,
        $user_id = null,
        ?array $establishment_ids = null
    ): array {
        $query = Transaction::query()
            ->where('type', 'purchases')
            ->where('status', 'approved')
            ->select(
                DB::raw('SUM(final_total) as final_total_sum'),
                DB::raw('SUM((SELECT COALESCE(SUM(tp.amount), 0) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid'),
                DB::raw('SUM(total_before_tax) as total_before_tax_sum'),
            );

        $this->applyDateFilter($query, $start_date, $end_date);
        $this->applyEstablishmentFilter($query, $establishment_ids);

        if (! empty($user_id)) {
            $query->where('transactions.created_by', $user_id);
        }

        $purchase_details = $query->first();

        return [
            'total_purchase_inc_tax' => (float) ($purchase_details->final_total_sum ?? 0),
            'total_purchase_exc_tax' => (float) ($purchase_details->total_before_tax_sum ?? 0),
            'purchase_due' => (float) (($purchase_details->final_total_sum ?? 0) - ($purchase_details->total_paid ?? 0)),
        ];
    }

    /**
     * @param  array<int>|null  $establishment_ids
     */
    public function getSellTotals(
        ?string $start_date = null,
        ?string $end_date = null,
        $created_by = null,
        ?array $establishment_ids = null
    ): array {
        $query = Transaction::query()
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'approved')
            ->select(
                DB::raw('SUM(final_total) as total_sell'),
                DB::raw('SUM(final_total - tax_amount) as total_exc_tax'),
                DB::raw('SUM(final_total - (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)), 0) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id))  as total_due'),
                DB::raw('SUM(total_before_tax) as total_before_tax'),
            );

        $this->applyDateFilter($query, $start_date, $end_date, 'transactions.transaction_date');
        $this->applyEstablishmentFilter($query, $establishment_ids);

        if (! empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }

        $sell_details = $query->first();

        return [
            'total_sell_inc_tax' => (float) ($sell_details->total_sell ?? 0),
            'total_sell_exc_tax' => (float) ($sell_details->total_before_tax ?? 0),
            'invoice_due' => (float) ($sell_details->total_due ?? 0),
        ];
    }

    /**
     * @param  array<int|string>  $transaction_types
     * @param  array<int>|null  $establishment_ids
     */
    public function getTransactionTotals(
        $transaction_types,
        ?string $start_date = null,
        ?string $end_date = null,
        $created_by = null,
        ?array $establishment_ids = null
    ): array {
        $query = Transaction::query();

        $this->applyDateFilter($query, $start_date, $end_date);
        $this->applyEstablishmentFilter($query, $establishment_ids);

        if (! empty($created_by)) {
            $query->where('transactions.created_by', $created_by);
        }

        if (in_array('purchases-return', $transaction_types, true)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='purchases-return' AND transactions.status='approved', final_total, 0)) as total_purchase_return_inc_tax"),
                DB::raw("SUM(IF(transactions.type='purchases-return' AND transactions.status='approved', total_before_tax, 0)) as total_purchase_return_exc_tax")
            );
        }

        if (in_array('sell-return', $transaction_types, true)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell-return' AND transactions.status='approved', final_total, 0)) as total_sell_return_inc_tax"),
                DB::raw("SUM(IF(transactions.type='sell-return' AND transactions.status='approved', total_before_tax, 0)) as total_sell_return_exc_tax"),
                DB::raw("SUM(IF(transactions.type='sell-return' AND transactions.status='approved', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_sell_return_discount")
            );
        }

        if (in_array('purchases', $transaction_types, true)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='purchases' AND transactions.status='approved', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_purchase_discount")
            );
        }

        if (in_array('sell', $transaction_types, true)) {
            $query->addSelect(
                DB::raw("SUM(IF(transactions.type='sell' AND transactions.status='approved', IF(discount_type = 'percentage', COALESCE(discount_amount, 0)*total_before_tax/100, COALESCE(discount_amount, 0)), 0)) as total_sell_discount")
            );
        }

        $transaction_totals = $query->first();
        $output = [];

        if (in_array('purchases-return', $transaction_types, true)) {
            $output['total_purchase_return_inc_tax'] = (float) ($transaction_totals->total_purchase_return_inc_tax ?? 0);
            $output['total_purchase_return_exc_tax'] = (float) ($transaction_totals->total_purchase_return_exc_tax ?? 0);
        }

        if (in_array('sell-return', $transaction_types, true)) {
            $output['total_sell_return_inc_tax'] = (float) ($transaction_totals->total_sell_return_inc_tax ?? 0);
            $output['total_sell_return_exc_tax'] = (float) ($transaction_totals->total_sell_return_exc_tax ?? 0);
            $output['total_sell_return_discount'] = (float) ($transaction_totals->total_sell_return_discount ?? 0);
        }

        if (in_array('purchases', $transaction_types, true)) {
            $output['total_purchase_discount'] = (float) ($transaction_totals->total_purchase_discount ?? 0);
        }

        if (in_array('sell', $transaction_types, true)) {
            $output['total_sell_discount'] = (float) ($transaction_totals->total_sell_discount ?? 0);
        }

        return $output;
    }

    /**
     * @param  array<int>|null  $establishment_ids
     */
    public function getGrossProfit(
        ?string $start_date = null,
        ?string $end_date = null,
        $user_id = null,
        ?array $establishment_ids = null
    ): float {
        $query = TransactionSellLine::query()
            ->join('transactions as sale', 'transaction_sell_lines.transaction_id', '=', 'sale.id')
            ->leftJoin('transactione_purchases_lines as TPL', function ($join) {
                $join->on('transaction_sell_lines.transaction_id', '=', 'TPL.transaction_id')
                    ->on('transaction_sell_lines.product_id', '=', 'TPL.product_id');
            })
            ->join('product_products as P', 'transaction_sell_lines.product_id', '=', 'P.id')
            ->where('sale.type', 'sell')
            ->where('sale.status', 'approved')
            ->where(function ($q) {
                $q->whereNull('transaction_sell_lines.is_show')
                    ->orWhere('transaction_sell_lines.is_show', '1')
                    ->orWhere('transaction_sell_lines.is_show', 1);
            });

        $this->applyDateFilter($query, $start_date, $end_date, 'sale.transaction_date');
        if (! empty($establishment_ids)) {
            $query->whereIn('sale.establishment_id', $establishment_ids);
        }

        if (! empty($user_id)) {
            $query->where('sale.created_by', $user_id);
        }

        $gross_profit_obj = $query->selectRaw('
            SUM(
                (CAST(transaction_sell_lines.qyt AS DECIMAL(16,4)) - COALESCE(CAST(TPL.qyt AS DECIMAL(16,4)), 0)) *
                (CAST(transaction_sell_lines.unit_price_inc_tax AS DECIMAL(16,4)) - COALESCE(CAST(TPL.unit_price_inc_tax AS DECIMAL(16,4)), CAST(P.cost AS DECIMAL(16,4)), 0))
            ) AS gross_profit
        ')->first();

        return (float) ($gross_profit_obj->gross_profit ?? 0);
    }

    protected function applyDateFilter($query, ?string $start_date, ?string $end_date, string $column = 'transactions.transaction_date'): void
    {
        if (! empty($start_date) && ! empty($end_date)) {
            if ($start_date === $end_date) {
                $query->whereDate($column, $start_date);
            } else {
                $query->whereDate($column, '>=', $start_date)
                    ->whereDate($column, '<=', $end_date);
            }

            return;
        }

        if (empty($start_date) && ! empty($end_date)) {
            $query->whereDate($column, '<=', $end_date);
        }
    }

    /**
     * @param  array<int>|null  $establishment_ids
     */
    protected function applyEstablishmentFilter($query, ?array $establishment_ids, string $table = 'transactions'): void
    {
        if (! empty($establishment_ids)) {
            $query->whereIn("{$table}.establishment_id", $establishment_ids);
        }
    }

    /**
     * Filters for sell-line profit breakdown queries (alias: sale).
     *
     * @param  array<int>|null  $establishment_ids
     */
    public function applySaleTransactionFilters(
        $query,
        ?string $start_date = null,
        ?string $end_date = null,
        ?array $establishment_ids = null
    ): void {
        $this->applyDateFilter($query, $start_date, $end_date, 'sale.transaction_date');
        if (! empty($establishment_ids)) {
            $query->whereIn('sale.establishment_id', $establishment_ids);
        }
    }
}
