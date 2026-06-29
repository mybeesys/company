<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\General\Support\TransactionLineTaxRate;

/**
 * Maps POS / Flutter stor-sales-invoice payload fields to DB columns used by the web UI.
 */
final class PosSalesInvoiceMapper
{
    public static function resolveInvoiceType(Request $request): string
    {
        $invoiceType = strtolower(trim((string) $request->input('invoice_type', '')));
        if (in_array($invoiceType, ['cash', 'due'], true)) {
            return $invoiceType;
        }

        $paymentStatus = strtolower(trim((string) $request->input('payment_status', '')));
        if (in_array($paymentStatus, ['cash', 'due'], true)) {
            return $paymentStatus;
        }

        if (in_array($paymentStatus, ['paid', 'partial'], true)) {
            return 'cash';
        }

        return 'due';
    }

    public static function resolveTaxableAfterDiscount(Request $request): float
    {
        $beforeDiscount = (float) ($request->input('total_before_discount') ?? 0);
        $afterDiscount = (float) ($request->input('total_after_discount') ?? 0);
        $totalTax = (float) ($request->input('total_tax') ?? 0);
        $finalTotal = (float) ($request->input('total_paid') ?? 0);
        $discountValue = (float) ($request->input('discount_value') ?? 0);

        if ($discountValue <= 0 && $beforeDiscount > 0) {
            return $beforeDiscount;
        }

        if ($afterDiscount > 0) {
            if ($totalTax > 0 && $finalTotal > 0 && abs($afterDiscount - $finalTotal) < 0.05) {
                return round($finalTotal - $totalTax, 4);
            }

            return $afterDiscount;
        }

        if ($finalTotal > 0 && $totalTax > 0) {
            return round($finalTotal - $totalTax, 4);
        }

        return $beforeDiscount;
    }

    /**
     * @param  array{discount_amount?: float, totalAfterDiscount?: float, tax_amount?: float, final_total?: float}  $overrides
     * @return array<string, mixed>
     */
    public static function mapTransactionAttributes(Request $request, array $overrides = []): array
    {
        $discountValue = (float) ($overrides['discount_amount'] ?? $request->input('discount_value') ?? 0);
        $totalTax = (float) ($overrides['tax_amount'] ?? $request->input('total_tax') ?? 0);
        $finalTotal = (float) ($overrides['final_total'] ?? $request->input('total_paid') ?? 0);
        $totalAfterDiscount = (float) ($overrides['totalAfterDiscount'] ?? self::resolveTaxableAfterDiscount($request));
        $discountType = trim((string) $request->input('discount_type', ''));

        return [
            'type' => 'sell',
            'local_id' => $request->id,
            'invoice_type' => self::resolveInvoiceType($request),
            'due_date' => null,
            'transaction_date' => Carbon::parse($request->created_at)->format('Y-m-d H:i:s'),
            'contact_id' => $request->customer_id,
            'discount_amount' => $discountValue,
            'discount_type' => $discountType !== '' ? $discountType : null,
            'total_before_tax' => $request->total_before_discount,
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

        $priceWithTax = (float) ($product->price_with_tax_after_discount ?? 0);
        if ($priceWithTax <= 0) {
            $priceWithTax = (float) ($product->price_with_tax ?? 0);
        }
        if ($priceWithTax <= 0 && $qty > 0) {
            $priceWithTax = round($lineTotalBeforeVat + (float) ($product->tax_value ?? 0), 4);
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
            'unit_price_inc_tax' => $priceWithTax,
            'tax_id' => TransactionLineTaxRate::normalizeForStorage($product->tax_id ?? null),
            'tax_value' => $product->tax_value,
            'total_before_vat' => $lineTotalBeforeVat,
        ];
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

        $priceWithTax = (float) ($modifier->price_with_tax ?? 0);
        if ($priceWithTax <= 0 && $qty > 0) {
            $priceWithTax = round($lineTotalBeforeVat + (float) ($modifier->tax_value ?? 0), 4);
        }

        $discountType = trim((string) ($modifier->discount_type ?? ''));

        return [
            'product_id' => $modifier->modifier_id,
            'qyt' => $modifier->quantity,
            'unit_price_before_discount' => $price,
            'unit_price' => $price,
            'discount_type' => $discountType !== '' ? $discountType : null,
            'discount_amount' => $modifier->discount_amount,
            'unit_price_inc_tax' => $priceWithTax,
            'tax_value' => $modifier->tax_value,
            'total_before_vat' => $lineTotalBeforeVat,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function paymentRequestAttributes(Request $request, object $payment, ?int $accountId): array
    {
        return [
            'paid_amount' => $payment->amount,
            'amount' => $payment->amount,
            'payment_method_id' => $payment->method_id,
            'created_by' => $request->user_id,
            'shift_id' => $payment->shift_id ?? $request->shift_id,
            'cash_account' => $accountId,
            'account_id' => $accountId,
        ];
    }
}
