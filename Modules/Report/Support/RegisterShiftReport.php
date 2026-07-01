<?php

declare(strict_types=1);

namespace Modules\Report\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\CashRegister;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionSellLine;
use stdClass;

/**
 * Aggregates POS shift sales from transactions / transaction_payments
 * (cash_registers.shift_number ↔ transactions.shift_number or transactions.local_id).
 */
final class RegisterShiftReport
{
    /** @var array<string, string> payment_methods.name_en → register_details aggregate field */
    public const PAYMENT_FIELD_MAP = [
        'cash' => 'total_cash',
        'card' => 'total_card',
        'bank_check' => 'total_cheque',
        'bank_transfer' => 'total_bank_transfer',
        'prepaid' => 'total_advance',
    ];

    public static function applyShiftScope(Builder $query, string $shiftNumber, string $tableAlias = 'transactions'): Builder
    {
        return $query->where(function (Builder $q) use ($shiftNumber, $tableAlias) {
            $q->where("{$tableAlias}.shift_number", $shiftNumber)
                ->orWhere("{$tableAlias}.local_id", $shiftNumber);
        });
    }

    public static function shiftPaymentTotalsSubquery(): QueryBuilder
    {
        return DB::table('transactions as t')
            ->join('transaction_payments as tp', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'tp.payment_method_id')
            ->where('t.status', 'approved')
            ->whereIn('t.type', ['sell', 'sell-return'])
            ->where(function (QueryBuilder $q) {
                $q->where(function (QueryBuilder $inner) {
                    $inner->whereNotNull('t.shift_number')
                        ->where('t.shift_number', '!=', '');
                })->orWhere(function (QueryBuilder $inner) {
                    $inner->whereNotNull('t.local_id')
                        ->where('t.local_id', '!=', '');
                });
            })
            ->selectRaw("
                COALESCE(NULLIF(t.shift_number, ''), t.local_id) as shift_key,
                SUM(CASE WHEN pm.name_en = 'cash' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_cash_payment,
                SUM(CASE WHEN pm.name_en = 'bank_check' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_cheque_payment,
                SUM(CASE WHEN pm.name_en = 'card' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_card_payment,
                SUM(CASE WHEN pm.name_en = 'bank_transfer' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_bank_transfer_payment,
                SUM(CASE WHEN pm.name_en = 'prepaid' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_prepaid_payment,
                SUM(CASE WHEN t.type = 'sell' THEN tp.amount ELSE 0 END) as total_sale,
                SUM(CASE WHEN t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_refund
            ")
            ->groupBy('shift_key');
    }

    /**
     * @return array{product_details: \Illuminate\Support\Collection, transaction_details: stdClass|null}
     */
    public static function transactionDetails(CashRegister $register): array
    {
        $shiftNumber = (string) $register->shift_number;

        $transactionQuery = Transaction::query()
            ->where('status', 'approved');
        self::applyShiftScope($transactionQuery, $shiftNumber);

        $sellIds = (clone $transactionQuery)->where('type', 'sell')->pluck('id');

        $product_details = TransactionSellLine::query()
            ->join('transactions as t', 'transaction_sell_lines.transaction_id', '=', 't.id')
            ->join('product_products as p', 'transaction_sell_lines.product_id', '=', 'p.id')
            ->whereIn('transaction_sell_lines.transaction_id', $sellIds)
            ->select(
                'p.id',
                'p.name_ar as product_name_ar',
                'p.name_en as product_name_en',
                DB::raw('SUM(transaction_sell_lines.qyt) as total_quantity'),
                DB::raw('SUM(transaction_sell_lines.unit_price_inc_tax * transaction_sell_lines.qyt) as total_amount')
            )
            ->groupBy('p.id', 'p.name_ar', 'p.name_en')
            ->get();

        $transaction_details = (clone $transactionQuery)
            ->where('type', 'sell')
            ->select(
                DB::raw('SUM(tax_amount) as total_tax'),
                DB::raw('SUM(IF(discount_type = "percent", total_before_tax*discount_amount/100, discount_amount)) as total_discount'),
                DB::raw('SUM(final_total) as total_sales'),
            )
            ->first();

        return [
            'product_details' => $product_details,
            'transaction_details' => $transaction_details,
        ];
    }

    public static function paymentTotals(CashRegister $register): stdClass
    {
        $shiftNumber = (string) $register->shift_number;

        $row = DB::table('transactions as t')
            ->join('transaction_payments as tp', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'tp.payment_method_id')
            ->where('t.status', 'approved')
            ->whereIn('t.type', ['sell', 'sell-return'])
            ->where(function (QueryBuilder $q) use ($shiftNumber) {
                $q->where('t.shift_number', $shiftNumber)
                    ->orWhere('t.local_id', $shiftNumber);
            })
            ->selectRaw("
                SUM(CASE WHEN pm.name_en = 'cash' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_cash,
                SUM(CASE WHEN pm.name_en = 'cash' AND t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_cash_refund,
                SUM(CASE WHEN pm.name_en = 'bank_check' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_cheque,
                SUM(CASE WHEN pm.name_en = 'bank_check' AND t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_cheque_refund,
                SUM(CASE WHEN pm.name_en = 'card' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_card,
                SUM(CASE WHEN pm.name_en = 'card' AND t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_card_refund,
                SUM(CASE WHEN pm.name_en = 'bank_transfer' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_bank_transfer,
                SUM(CASE WHEN pm.name_en = 'bank_transfer' AND t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_bank_transfer_refund,
                SUM(CASE WHEN pm.name_en = 'prepaid' AND t.type = 'sell' THEN tp.amount ELSE 0 END) as total_advance,
                SUM(CASE WHEN pm.name_en = 'prepaid' AND t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_advance_refund,
                SUM(CASE WHEN t.type = 'sell' THEN tp.amount ELSE 0 END) as total_sale,
                SUM(CASE WHEN t.type = 'sell-return' THEN tp.amount ELSE 0 END) as total_refund
            ")
            ->first();

        return $row ?? new stdClass;
    }

    public static function mergePaymentTotalsInto(stdClass $registerDetails, stdClass $shiftTotals): stdClass
    {
        foreach (self::PAYMENT_FIELD_MAP as $saleField) {
            $refundField = $saleField.'_refund';
            $registerDetails->$saleField = (float) ($shiftTotals->$saleField ?? 0);
            $registerDetails->$refundField = (float) ($shiftTotals->$refundField ?? 0);
        }

        $registerDetails->total_sale = (float) ($shiftTotals->total_sale ?? 0);
        $registerDetails->total_refund = (float) ($shiftTotals->total_refund ?? 0);

        return $registerDetails;
    }

    /**
     * Payment lines from POS transactions for the register transactions table.
     */
    public static function paymentLines(CashRegister $register): Collection
    {
        $shiftNumber = (string) $register->shift_number;

        return DB::table('transactions as t')
            ->join('transaction_payments as tp', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'tp.payment_method_id')
            ->where('t.status', 'approved')
            ->whereIn('t.type', ['sell', 'sell-return'])
            ->where(function (QueryBuilder $q) use ($shiftNumber) {
                $q->where('t.shift_number', $shiftNumber)
                    ->orWhere('t.local_id', $shiftNumber);
            })
            ->orderByDesc('tp.created_at')
            ->select(
                'tp.created_at',
                't.type as transaction_type',
                't.invoice_no',
                'pm.name_en as pay_method',
                'pm.name_ar as pay_method_ar',
                'tp.amount'
            )
            ->get();
    }
}
