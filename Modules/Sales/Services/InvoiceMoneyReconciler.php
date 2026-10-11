<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\General\Support\Money;

/**
 * Recalculates invoice line and header money in halalas so browser separators
 * and float drift cannot change SAR by 0.01.
 */
final class InvoiceMoneyReconciler
{
    /**
     * @param  list<array<string, mixed>|object>  $products
     * @return array{
     *     products: list<array<string, mixed>>,
     *     total_before_vat: float,
     *     invoice_discount_amount: float,
     *     total_after_discount: float,
     *     total_vat: float,
     *     total_after_vat: float
     * }
     */
    public static function reconcile(array $products, mixed $invoiceDiscount, mixed $invoiceDiscountType): array
    {
        $lines = [];
        $totalBeforeVatH = 0;

        foreach ($products as $product) {
            $row = is_array($product) ? $product : (array) $product;
            $qty = Money::parse($row['qty'] ?? 0);
            $unitPrice = Money::parse($row['unit_price'] ?? 0);
            $discountValue = Money::parse($row['discount'] ?? 0);
            $discountType = (string) ($row['discount_type'] ?? 'fixed');
            $taxRate = Money::parse($row['tax_vat'] ?? 0);
            $inclusive = self::isTruthy($row['inclusive'] ?? false);

            $lineGross = Money::round($qty * $unitPrice);
            $discountAmount = $discountType === 'percent'
                ? Money::percent($lineGross, $discountValue)
                : Money::round($discountValue);
            $afterDiscount = Money::round(max(0, $lineGross - $discountAmount));

            if ($inclusive && $taxRate > 0) {
                $net = Money::round($afterDiscount / (1 + $taxRate / 100));
                $vat = Money::sub($afterDiscount, $net);
                $gross = $afterDiscount;
            } elseif ($taxRate > 0) {
                $taxed = self::exclusiveTaxedLine($qty, $afterDiscount, $taxRate);
                $net = $taxed['net'];
                $vat = $taxed['vat'];
                $gross = $taxed['gross'];
            } else {
                $net = $afterDiscount;
                $vat = 0.0;
                $gross = $afterDiscount;
            }

            $row['qty'] = $qty;
            $row['unit_price'] = Money::round($unitPrice);
            $row['discount'] = $discountType === 'percent' ? Money::round($discountValue) : $discountAmount;
            $row['total_before_vat'] = $net;
            $row['vat_value'] = $vat;
            $row['total_after_vat'] = $gross;
            $row['_tax_rate'] = $taxRate;
            $row['_inclusive'] = $inclusive;
            $row['_qty'] = $qty;

            $lines[] = $row;
            $totalBeforeVatH += Money::halalas($net);
        }

        $totalBeforeVat = Money::fromHalalas($totalBeforeVatH);
        $discountType = (string) $invoiceDiscountType;
        $invoiceDiscountInput = Money::parse($invoiceDiscount);
        $totalDiscount = $discountType === 'percent'
            ? Money::percent($totalBeforeVat, $invoiceDiscountInput)
            : Money::round($invoiceDiscountInput);
        if ($totalDiscount > $totalBeforeVat) {
            $totalDiscount = $totalBeforeVat;
        }

        $vatH = 0;
        $allocatedDiscountH = 0;
        $lastIndex = count($lines) - 1;
        foreach ($lines as $i => &$row) {
            $net = (float) $row['total_before_vat'];
            $taxRate = (float) $row['_tax_rate'];
            $inclusive = (bool) $row['_inclusive'];
            $rowGross = (float) $row['total_after_vat'];

            if ($i === $lastIndex) {
                $share = Money::fromHalalas(Money::halalas($totalDiscount) - $allocatedDiscountH);
            } else {
                $share = $totalBeforeVat > 0
                    ? Money::round($net / $totalBeforeVat * $totalDiscount)
                    : 0.0;
                $allocatedDiscountH += Money::halalas($share);
            }

            $adjustedNet = Money::round(max(0, $net - $share));

            if ($inclusive && $taxRate > 0 && $net > 0) {
                $adjustedGross = Money::round($rowGross * ($adjustedNet / $net));
                $lineVat = Money::sub($adjustedGross, $adjustedNet);
            } elseif ($taxRate > 0) {
                $qty = Money::parse($row['qty'] ?? 0);
                $lineVat = self::exclusiveTaxedLine($qty, $adjustedNet, $taxRate)['vat'];
            } else {
                $lineVat = (float) $row['vat_value'];
            }

            $vatH += Money::halalas($lineVat);
            unset($row['_tax_rate'], $row['_inclusive'], $row['_qty']);
        }
        unset($row);

        $totalVat = Money::fromHalalas($vatH);
        $totalAfterDiscount = Money::sub($totalBeforeVat, $totalDiscount);
        $totalAfterVat = Money::add($totalAfterDiscount, $totalVat);

        return [
            'products' => $lines,
            'total_before_vat' => $totalBeforeVat,
            'invoice_discount_amount' => $totalDiscount,
            'total_after_discount' => $totalAfterDiscount,
            'total_vat' => $totalVat,
            'total_after_vat' => $totalAfterVat,
        ];
    }

    /**
     * Exclusive VAT: round the tax-inclusive unit price to 2dp, then × qty.
     * 3 × 33.91 @ 15% → unit 39.00, line 117.00 (not 116.99 from tax-on-line-net).
     *
     * @return array{net: float, vat: float, gross: float}
     */
    public static function exclusiveTaxedLine(float $qty, float $net, float $ratePercent): array
    {
        $net = Money::round($net);
        if ($ratePercent <= 0) {
            return ['net' => $net, 'vat' => 0.0, 'gross' => $net];
        }
        if ($qty <= 0) {
            $vat = Money::percent($net, $ratePercent);

            return ['net' => $net, 'vat' => $vat, 'gross' => Money::add($net, $vat)];
        }

        $unitGross = Money::round(($net / $qty) * (1 + $ratePercent / 100));
        $gross = Money::round($unitGross * $qty);
        $vat = Money::sub($gross, $net);
        if ($vat < 0) {
            $vat = 0.0;
            $gross = $net;
        }

        return ['net' => $net, 'vat' => $vat, 'gross' => $gross];
    }

    private static function isTruthy(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'on';
    }
}
