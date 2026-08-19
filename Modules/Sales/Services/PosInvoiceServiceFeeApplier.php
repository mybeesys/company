<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Http\Request;
use Modules\Establishment\Services\EstablishmentPaymentAccountResolver;
use Modules\General\Models\Setting;

/**
 * POS adapter for the same web invoice service-fee engine.
 * Does nothing unless the cashier explicitly sends selected fee ids.
 */
final class PosInvoiceServiceFeeApplier
{
    public static function isEnabled(): bool
    {
        $setting = Setting::where('key', 'toggleServiceFees')->value('value');

        return $setting === null ? true : ((int) $setting === 1);
    }

    /**
     * @return list<int>|null  null = field omitted (leave the sale unchanged)
     */
    public static function appliedIdsFromRequest(Request $request): ?array
    {
        $hasIds = $request->exists('applied_service_fee_ids');
        $hasFees = $request->exists('service_fees');
        if (! $hasIds && ! $hasFees) {
            return null;
        }

        $ids = [];
        foreach ((array) $request->input('applied_service_fee_ids', []) as $id) {
            $ids[] = (int) $id;
        }

        foreach ((array) $request->input('service_fees', []) as $row) {
            if (is_array($row)) {
                $ids[] = (int) ($row['id'] ?? $row['service_fee_id'] ?? 0);
            } elseif (is_object($row)) {
                $ids[] = (int) ($row->id ?? $row->service_fee_id ?? 0);
            } else {
                $ids[] = (int) $row;
            }
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id) => $id > 0)));
    }

    /**
     * @param  list<object>|array<int, mixed>  $products
     * @return array{
     *     fee_amount: float,
     *     fee_tax: float,
     *     lines: list<array<string, mixed>>,
     *     applied_ids: list<int>,
     *     product_tax: float,
     *     product_total: float,
     *     tax_amount: float,
     *     final_total: float
     * }|null
     */
    public static function apply(
        Request $request,
        mixed $products,
        int $establishmentId,
        float $productTax,
        float $productAfterDiscount,
        float $productFinal,
        float $productBeforeTax,
        float $invoiceDiscount
    ): ?array {
        $appliedIds = self::appliedIdsFromRequest($request);
        if ($appliedIds === null || $establishmentId <= 0 || ! self::isEnabled()) {
            return null;
        }

        $clientFeeAmount = (float) ($request->input('service_fee_amount') ?? 0);
        $clientFeeTax = (float) ($request->input('service_fee_tax') ?? 0);
        if ($clientFeeAmount > 0 || $clientFeeTax > 0) {
            $productTax = round(max(0, $productTax - $clientFeeTax), 2);
            $productFinal = round(max(0, $productFinal - $clientFeeAmount - $clientFeeTax), 2);
        }

        $cashAccountId = self::resolveCashAccountId($request, $establishmentId);

        $result = InvoiceServiceFeeCalculator::forInvoice(
            $establishmentId,
            self::collectLines($products),
            $productBeforeTax,
            $invoiceDiscount,
            $productAfterDiscount,
            $productTax,
            $productFinal,
            $appliedIds,
            $cashAccountId,
            self::transactionDate($request),
            self::nullablePositiveInt($request->input('guest_count')),
            self::nullablePositiveInt($request->input('dining_type_id'))
        );

        $feeAmount = (float) $result['fee_amount'];
        $feeTax = (float) $result['fee_tax'];

        return [
            'fee_amount' => $feeAmount,
            'fee_tax' => $feeTax,
            'lines' => $result['lines'],
            'applied_ids' => $result['applied_ids'],
            'product_tax' => $productTax,
            'product_total' => $productFinal,
            'tax_amount' => round($productTax + $feeTax, 2),
            'final_total' => round($productFinal + $feeAmount + $feeTax, 2),
        ];
    }

    /**
     * @param  list<object>|array<int, mixed>|null  $products
     * @return list<array{qty: float, net: float, vat: float, gross: float, tax_rate: float}>
     */
    public static function collectLines(mixed $products): array
    {
        $lines = [];
        foreach ($products ?? [] as $product) {
            $product = is_object($product) ? $product : (object) $product;
            $lines[] = self::lineFromItem($product);

            foreach ($product->order_item_modifiers ?? [] as $modifier) {
                $modifier = is_object($modifier) ? $modifier : (object) $modifier;
                $lines[] = self::lineFromItem($modifier);
            }

            foreach ($product->order_item_combos ?? [] as $combo) {
                $combo = is_object($combo) ? $combo : (object) $combo;
                $lines[] = self::lineFromItem($combo, defaultQty: 1.0);
            }
        }

        return $lines;
    }

    /**
     * @return array{qty: float, net: float, vat: float, gross: float, tax_rate: float}
     */
    private static function lineFromItem(object $item, float $defaultQty = 0.0): array
    {
        $qty = (float) ($item->quantity ?? $defaultQty);
        $price = (float) ($item->price_after_discount ?? $item->price ?? 0);
        $discount = (float) ($item->discount_amount ?? 0);
        $net = isset($item->total_before_vat) && (float) $item->total_before_vat > 0
            ? (float) $item->total_before_vat
            : round(max(0, $qty * $price - $discount), 4);
        $vat = PosSalesInvoiceMapper::resolveLineTaxAmount($item, $qty > 0 ? $qty : 1.0, $net);
        $gross = $net + $vat;
        if (isset($item->price_with_tax_after_discount) && (float) $item->price_with_tax_after_discount > 0 && $qty > 0) {
            $gross = round((float) $item->price_with_tax_after_discount * $qty, 4);
        } elseif (isset($item->price_with_tax) && (float) $item->price_with_tax > 0 && $qty > 0 && $gross <= 0) {
            $gross = round((float) $item->price_with_tax * $qty, 4);
        }

        $taxRate = 0.0;
        if ($net > 0.0001 && $vat > 0) {
            $taxRate = ($vat / $net) * 100;
        }

        return [
            'qty' => $qty,
            'net' => $net,
            'vat' => $vat,
            'gross' => $gross,
            'tax_rate' => $taxRate,
        ];
    }

    private static function resolveCashAccountId(Request $request, int $establishmentId): ?int
    {
        $payments = $request->input('payments', []);
        if (! is_array($payments)) {
            return null;
        }

        foreach ($payments as $payment) {
            $payment = is_array($payment) ? (object) $payment : $payment;
            $methodId = (int) ($payment->method_id ?? 0);
            if ($methodId === -1) {
                $methodId = EstablishmentPaymentAccountResolver::resolveCashMethodId($establishmentId) ?? 0;
            }
            if ($methodId <= 0) {
                continue;
            }

            $resolved = EstablishmentPaymentAccountResolver::resolveForCashierPayment($establishmentId, $methodId);
            if ($resolved['ok'] ?? false) {
                return (int) $resolved['account_id'];
            }
        }

        return null;
    }

    private static function transactionDate(Request $request): ?string
    {
        $raw = $request->input('created_at') ?: $request->input('transaction_date');

        return $raw !== null && $raw !== '' ? (string) $raw : null;
    }

    private static function nullablePositiveInt(mixed $value): ?int
    {
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
