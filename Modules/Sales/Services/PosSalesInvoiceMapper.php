<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Establishment\Models\EstablishmentPaymentAccount;
use Modules\General\Models\PaymentMethod;
use Modules\General\Support\TransactionLineTaxRate;
use Modules\Product\Models\ProductComboItem;

/**
 * Maps POS / Flutter stor-sales-invoice payload fields to DB columns used by the web UI.
 */
final class PosSalesInvoiceMapper
{
    public static function resolveInvoiceType(Request $request): string
    {
        $invoiceType = strtolower(trim((string) $request->input('invoice_type', '')));
        if ($invoiceType === 'credit') {
            $invoiceType = 'due';
        }
        if (in_array($invoiceType, ['cash', 'due'], true)) {
            return $invoiceType;
        }

        $paymentStatus = strtolower(trim((string) $request->input('payment_status', '')));
        if ($paymentStatus === 'credit') {
            $paymentStatus = 'due';
        }
        if (in_array($paymentStatus, ['cash', 'due'], true)) {
            return $paymentStatus;
        }

        if (in_array($paymentStatus, ['paid', 'partial'], true)) {
            return 'cash';
        }

        $payments = $request->input('payments');
        if (is_array($payments) && $payments !== []) {
            return self::paymentsIndicateDeferred($request, $payments) ? 'due' : 'cash';
        }

        return 'due';
    }

    /**
     * @param  array<int, mixed>  $payments
     */
    private static function paymentsIndicateDeferred(Request $request, array $payments): bool
    {
        $establishmentId = (int) $request->input('establishment_id');
        $methodIds = [];
        foreach ($payments as $payment) {
            $methodId = (int) (is_array($payment) ? ($payment['method_id'] ?? 0) : ($payment->method_id ?? 0));
            if ($methodId > 0 && $methodId !== -1) {
                $methodIds[] = $methodId;
            }
        }

        if ($methodIds === [] || $establishmentId <= 0) {
            return false;
        }

        $methods = EstablishmentPaymentAccount::query()
            ->forEstablishment($establishmentId)
            ->whereIn('id', array_values(array_unique($methodIds)))
            ->get(['payment_method_key', 'name_en', 'name_ar']);

        foreach ($methods as $method) {
            if (self::isDeferredPaymentMethod($method)) {
                return true;
            }
        }

        return false;
    }

    public static function isDeferredPaymentMethod(EstablishmentPaymentAccount $method): bool
    {
        $hay = strtolower(trim(
            (string) $method->payment_method_key.' '.
            (string) $method->name_en.' '.
            (string) $method->name_ar
        ));

        return str_contains($hay, 'due')
            || str_contains($hay, 'credit')
            || str_contains($hay, 'deferred')
            || str_contains($hay, 'آجل')
            || str_contains($hay, 'اجل')
            || str_contains($hay, 'ذمم');
    }

    public static function resolveTaxAmount(Request $request): float
    {
        $headerTax = (float) ($request->input('total_tax') ?? 0);
        if ($headerTax > 0) {
            return $headerTax;
        }

        return self::sumItemsTax($request);
    }

    public static function resolveTaxableAfterDiscount(Request $request): float
    {
        $beforeDiscount = (float) ($request->input('total_before_discount') ?? 0);
        $afterDiscount = (float) ($request->input('total_after_discount') ?? 0);
        $totalTax = self::resolveTaxAmount($request);
        $finalTotal = (float) ($request->input('total_paid') ?? 0);
        $itemsBeforeVat = self::sumItemsBeforeVat($request);

        if ($totalTax > 0 && $finalTotal > 0) {
            return round($finalTotal - $totalTax, 4);
        }

        if ($itemsBeforeVat > 0) {
            return $itemsBeforeVat;
        }

        if ($afterDiscount > 0) {
            if ($totalTax > 0 && $finalTotal > 0 && abs($afterDiscount - $finalTotal) < 0.05) {
                return round($finalTotal - $totalTax, 4);
            }

            return $afterDiscount;
        }

        return $beforeDiscount;
    }

    public static function resolveTotalBeforeTax(Request $request): float
    {
        $headerBefore = (float) ($request->input('total_before_discount') ?? 0);
        $finalTotal = (float) ($request->input('total_paid') ?? 0);
        $totalTax = self::resolveTaxAmount($request);
        $itemsBeforeVat = self::sumItemsBeforeVat($request);

        if ($itemsBeforeVat > 0 && ($headerBefore <= 0 || ($totalTax > 0 && abs($headerBefore - $finalTotal) < 0.05))) {
            return $itemsBeforeVat;
        }

        return $headerBefore > 0 ? $headerBefore : $itemsBeforeVat;
    }

    public static function sumItemsBeforeVat(Request $request): float
    {
        $items = $request->input('items', []);
        if (! is_array($items) || $items === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($items as $item) {
            $line = self::mapSellLineAttributes((object) $item);

            $sum += (float) ($line['total_before_vat'] ?? 0);
        }

        return round($sum, 4);
    }

    public static function sumItemsTax(Request $request): float
    {
        $items = $request->input('items', []);
        if (! is_array($items) || $items === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($items as $item) {
            $item = (object) $item;
            $qty = (float) ($item->quantity ?? 0);
            $lineBeforeVat = (float) (self::mapSellLineAttributes($item)['total_before_vat'] ?? 0);
            $sum += self::resolveLineTaxAmount($item, $qty, $lineBeforeVat);
        }

        return round($sum, 4);
    }

    /**
     * @param  array{discount_amount?: float, totalAfterDiscount?: float, tax_amount?: float, final_total?: float, total_before_tax?: float|null, purpose?: string|null, internal_consumption_type_id?: int|null}  $overrides
     * @return array<string, mixed>
     */
    public static function mapTransactionAttributes(Request $request, array $overrides = []): array
    {
        $discountValue = (float) ($overrides['discount_amount'] ?? $request->input('discount_value') ?? 0);
        $totalTax = (float) ($overrides['tax_amount'] ?? self::resolveTaxAmount($request));
        $finalTotal = (float) ($overrides['final_total'] ?? $request->input('total_paid') ?? 0);
        $totalAfterDiscount = (float) ($overrides['totalAfterDiscount'] ?? self::resolveTaxableAfterDiscount($request));
        $totalBeforeTax = (float) ($overrides['total_before_tax'] ?? self::resolveTotalBeforeTax($request));
        $discountType = trim((string) $request->input('discount_type', ''));
        $typeId = self::nullablePositiveInt(
            $overrides['internal_consumption_type_id'] ?? $request->input('internal_consumption_type_id')
        );
        $purpose = \Modules\Sales\Support\TransactionPurpose::normalize(
            $overrides['purpose'] ?? $request->input('purpose')
        );
        if ($typeId) {
            $purpose = \Modules\Sales\Support\TransactionPurpose::INTERNAL_CONSUMPTION;
        }

        return [
            'type' => 'sell',
            'purpose' => $purpose,
            'local_id' => $request->id,
            'invoice_type' => self::resolveInvoiceType($request),
            'due_date' => null,
            'transaction_date' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
            'contact_id' => $request->customer_id,
            'discount_amount' => $discountValue,
            'discount_type' => $discountType !== '' ? $discountType : null,
            'total_before_tax' => $totalBeforeTax,
            'totalAfterDiscount' => $totalAfterDiscount,
            'tax_amount' => $totalTax,
            'final_total' => $finalTotal,
            'created_by' => $request->user_id,
            'description' => $request->note,
            'status' => $request->status,
            'notice' => null,
            'invoice_no' => $request->invoice_number,
            'shift_number' => $request->shift_id,
            'establishment_id' => $overrides['establishment_id'] ?? $request->establishment_id,
            'device_id' => $request->device_id,
            'order_status' => 'inpreparation',
            'order_type' => $request->input('order_type'),
            'table_id' => $request->input('table_id'),
            'table_order_id' => $request->input('table_order_id'),
            'internal_consumption_type_id' => $typeId,
        ];
    }

    /**
     * @param  array{parent_id?: int, establishment_id?: int, discount_amount?: float, totalAfterDiscount?: float, tax_amount?: float, final_total?: float, total_before_tax?: float}  $overrides
     * @return array<string, mixed>
     */
    public static function mapReturnTransactionAttributes(Request $request, array $overrides = []): array
    {
        $discountValue = (float) ($overrides['discount_amount'] ?? $request->input('discount_value') ?? 0);
        $totalTax = (float) ($overrides['tax_amount'] ?? self::resolveTaxAmount($request));
        $finalTotal = (float) ($overrides['final_total'] ?? $request->input('total_paid') ?? 0);
        $totalAfterDiscount = (float) ($overrides['totalAfterDiscount'] ?? self::resolveTaxableAfterDiscount($request));
        $totalBeforeTax = (float) ($overrides['total_before_tax'] ?? self::resolveTotalBeforeTax($request));
        $discountType = trim((string) $request->input('discount_type', ''));

        return [
            'type' => 'sell-return',
            'local_id' => $request->id,
            'invoice_type' => self::resolveInvoiceType($request),
            'due_date' => null,
            'transaction_date' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
            'contact_id' => $request->customer_id,
            'parent_id' => $overrides['parent_id'] ?? null,
            'discount_amount' => $discountValue,
            'discount_type' => $discountType !== '' ? $discountType : null,
            'total_before_tax' => $totalBeforeTax,
            'totalAfterDiscount' => $totalAfterDiscount,
            'tax_amount' => $totalTax,
            'final_total' => $finalTotal,
            'created_by' => $request->user_id,
            'description' => $request->note,
            'status' => $request->status,
            'notice' => null,
            'invoice_no' => $request->invoice_number,
            'shift_number' => $request->shift_id,
            'establishment_id' => $overrides['establishment_id'] ?? $request->establishment_id,
            'device_id' => $request->device_id,
            'order_type' => $request->input('order_type'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapSellLineAttributes(object $product): array
    {
        $qty = (float) ($product->quantity ?? 0);
        $priceBefore = (float) ($product->price ?? 0);
        $priceAfter = (float) ($product->price_after_discount ?? 0);
        if ($priceAfter <= 0) {
            $priceAfter = $priceBefore;
        }

        $discountAmount = (float) ($product->discount_amount ?? 0);
        $lineTotalBeforeVat = isset($product->total_before_vat) && (float) $product->total_before_vat > 0
            ? (float) $product->total_before_vat
            : round($qty * $priceBefore - $discountAmount, 4);

        $lineTaxAmount = self::resolveLineTaxAmount($product, $qty, $lineTotalBeforeVat);
        $unitPriceWithTax = (float) ($product->price_with_tax_after_discount ?? 0);
        if ($unitPriceWithTax <= 0) {
            $unitPriceWithTax = (float) ($product->price_with_tax ?? 0);
        }
        $lineTotalIncTax = round($lineTotalBeforeVat + $lineTaxAmount, 4);
        if ($lineTotalIncTax <= 0 && $unitPriceWithTax > 0 && $qty > 0) {
            $lineTotalIncTax = round($unitPriceWithTax * $qty, 4);
        }

        $discountType = trim((string) ($product->discount_type ?? ''));

        return [
            'product_id' => $product->product_id,
            'qyt' => $product->quantity,
            'unit_id' => $product->unit_id ?? null,
            'unit_price_before_discount' => $priceBefore,
            'unit_price' => $priceAfter,
            'discount_type' => $discountType !== '' ? $discountType : null,
            'discount_amount' => $product->discount_amount,
            'unit_price_inc_tax' => $lineTotalIncTax,
            'tax_id' => TransactionLineTaxRate::normalizeForStorage($product->tax_id ?? null),
            'tax_value' => $lineTaxAmount,
            'total_before_vat' => $lineTotalBeforeVat,
            'note' => self::resolveItemNote($product),
        ];
    }

    public static function resolveItemNote(object $product): ?string
    {
        $note = trim((string) ($product->note ?? ''));

        return $note !== '' ? $note : null;
    }

    public static function resolveLineTaxAmount(object $product, float $qty, float $lineTotalBeforeVat): float
    {
        $taxValue = (float) ($product->tax_value ?? 0);
        if ($taxValue <= 0) {
            return 0.0;
        }

        $unitPriceWithTax = (float) ($product->price_with_tax ?? 0);
        $expectedLineTotal = $unitPriceWithTax > 0 && $qty > 0 ? round($unitPriceWithTax * $qty, 4) : 0.0;

        if ($qty > 1 && $expectedLineTotal > 0) {
            if (abs($lineTotalBeforeVat + ($taxValue * $qty) - $expectedLineTotal) < 0.05) {
                return round($taxValue * $qty, 4);
            }

            if (abs($lineTotalBeforeVat + $taxValue - $expectedLineTotal) < 0.05) {
                return round($taxValue, 4);
            }
        }

        if ($qty <= 1) {
            return round($taxValue, 4);
        }

        return round($taxValue * $qty, 4);
    }

    /**
     * Combo selections are stored as zero-priced component lines; the meal total lives on the parent item.
     *
     * @return array<string, mixed>
     */
    public static function mapComboLineAttributes(object $combo, ProductComboItem $comboItem): array
    {
        $qty = (float) ($combo->quantity ?? 1);
        $price = (float) ($combo->price ?? 0);
        $priceWithTax = (float) ($combo->price_with_tax ?? $price);

        return [
            'product_id' => (int) $comboItem->item_id,
            'combo_id' => (string) ($combo->option_id ?? $comboItem->item_id),
            'qyt' => $qty,
            'unit_price_before_discount' => $price,
            'unit_price' => $price,
            'discount_type' => null,
            'discount_amount' => null,
            'unit_price_inc_tax' => $priceWithTax > 0 ? $priceWithTax : $price,
            'tax_id' => null,
            'tax_value' => 0,
            'total_before_vat' => round($price * $qty, 4),
            'is_show' => '1',
        ];
    }

    public static function resolveComboOption(object $combo): ?ProductComboItem
    {
        $optionId = (int) ($combo->option_id ?? 0);
        if ($optionId <= 0) {
            return null;
        }

        $comboGroupId = (int) ($combo->combo_group_id ?? 0);

        $byPivotId = ProductComboItem::query()->where('id', $optionId);
        if ($comboGroupId > 0) {
            $byPivotId->where('combo_id', $comboGroupId);
        }

        $found = $byPivotId->first();
        if ($found) {
            return $found;
        }

        // POS / Flutter often sends product id (item_id) as option_id, not pivot row id.
        $byProductId = ProductComboItem::query()->where('item_id', $optionId);
        if ($comboGroupId > 0) {
            $byProductId->where('combo_id', $comboGroupId);
        }

        return $byProductId->first();
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapModifierLineAttributes(object $modifier): array
    {
        $qty = (float) ($modifier->quantity ?? 0);
        $price = (float) ($modifier->price ?? 0);
        $lineTotalBeforeVat = isset($modifier->total_before_vat) && (float) $modifier->total_before_vat > 0
            ? (float) $modifier->total_before_vat
            : round($qty * $price - (float) ($modifier->discount_amount ?? 0), 4);

        $lineTaxAmount = self::resolveLineTaxAmount($modifier, $qty, $lineTotalBeforeVat);
        $unitPriceWithTax = (float) ($modifier->price_with_tax ?? 0);
        $lineTotalIncTax = round($lineTotalBeforeVat + $lineTaxAmount, 4);
        if ($lineTotalIncTax <= 0 && $unitPriceWithTax > 0 && $qty > 0) {
            $lineTotalIncTax = round($unitPriceWithTax * $qty, 4);
        }

        $discountType = trim((string) ($modifier->discount_type ?? ''));

        return [
            'product_id' => $modifier->modifier_id,
            'modifier_id' => $modifier->modifier_id,
            'qyt' => $modifier->quantity,
            'unit_price_before_discount' => $price,
            'unit_price' => $price,
            'discount_type' => $discountType !== '' ? $discountType : null,
            'discount_amount' => $modifier->discount_amount,
            'unit_price_inc_tax' => $lineTotalIncTax,
            'tax_id' => TransactionLineTaxRate::normalizeForStorage($modifier->tax_id ?? null),
            'tax_value' => $lineTaxAmount,
            'total_before_vat' => $lineTotalBeforeVat,
        ];
    }

    public static function resolvePaymentMethodId(object $payment, ?PaymentMethod $resolvedMethod): int
    {
        $methodId = $payment->method_id ?? null;
        if ($methodId === -1 || $methodId === '-1') {
            return (int) ($resolvedMethod?->id ?? 0);
        }

        return (int) $methodId;
    }

    /**
     * @return array<string, mixed>
     */
    public static function paymentRequestAttributes(
        Request $request,
        object $payment,
        ?int $accountId,
        ?int $paymentMethodId = null,
    ): array {
        return [
            'paid_amount' => $payment->amount,
            'amount' => $payment->amount,
            'payment_method_id' => $paymentMethodId ?? $payment->method_id,
            'created_by' => $request->user_id,
            'shift_id' => $payment->shift_id ?? $request->shift_id,
            'cash_account' => $accountId,
            'account_id' => $accountId,
        ];
    }

    private static function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
