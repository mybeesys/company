<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Carbon\Carbon;
use Modules\Establishment\Models\EstablishmentServiceFee;
use Modules\Establishment\Services\EstablishmentServiceFeeResolver;

/**
 * Saudi/GCC invoice service-fee math.
 *
 * Order of computation (never mix product net with the fee):
 *  1. Product lines → net after line discount, exclusive of VAT
 *  2. Invoice-level discount → product net after discount + proportional product VAT
 *  3. Service fees on that result:
 *     - % before tax  → of product net after invoice discount (order) or line net (item)
 *     - % after tax   → of product grand total after invoice discount (order) or line gross (item)
 *     - fixed amount  → once on the invoice, or amount × qty per item line
 *  4. If the fee is taxable, VAT is added on the fee exclusive amount
 *     (item: inherit the line VAT rate; order: weighted average of product VAT).
 */
final class InvoiceServiceFeeCalculator
{
    /**
     * @param  list<array<string, mixed>>  $lines  qty, net, vat, gross, tax_rate
     * @param  list<int>|null  $appliedIds  when provided, only these fees are applied (user selection)
     * @return array{
     *     fee_amount: float,
     *     fee_tax: float,
     *     lines: list<array<string, mixed>>,
     *     applied_ids: list<int>
     * }
     */
    public static function forInvoice(
        int $establishmentId,
        array $lines,
        float $subtotalBeforeVat,
        float $invoiceDiscount,
        float $subtotalAfterDiscount,
        float $productVat,
        float $productTotalAfterVat,
        ?array $appliedIds,
        ?int $cashAccountId = null,
        ?string $transactionDate = null,
        ?int $guestCount = null,
        ?int $diningTypeId = null
    ): array {
        $catalog = EstablishmentServiceFeeResolver::invoiceCatalog($establishmentId);
        $context = [
            'lines' => $lines,
            'subtotal_before_vat' => $subtotalBeforeVat,
            'invoice_discount' => $invoiceDiscount,
            'subtotal_after_discount' => $subtotalAfterDiscount,
            'product_vat' => $productVat,
            'product_total' => $productTotalAfterVat,
            'cash_account_id' => $cashAccountId,
            'transaction_date' => $transactionDate,
            'guest_count' => $guestCount,
            'dining_type_id' => $diningTypeId,
        ];

        $selected = [];
        foreach ($catalog as $fee) {
            $id = (int) ($fee['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            if ($appliedIds !== null) {
                if (! in_array($id, $appliedIds, true)) {
                    continue;
                }
            } elseif (! self::shouldAutoApply($fee, $context)) {
                continue;
            }

            $computed = self::computeFee($fee, $context);
            $selected[] = $computed;
        }

        $feeAmount = round(array_sum(array_column($selected, 'fee_amount')), 2);
        $feeTax = round(array_sum(array_column($selected, 'tax_amount')), 2);

        return [
            'fee_amount' => $feeAmount,
            'fee_tax' => $feeTax,
            'lines' => $selected,
            'applied_ids' => array_map(fn ($row) => (int) $row['id'], $selected),
        ];
    }

    /**
     * @param  array<string, mixed>  $fee
     * @param  array<string, mixed>  $context
     */
    public static function shouldAutoApply(array $fee, array $context): bool
    {
        if (! ($fee['is_active'] ?? $fee['active'] ?? false)) {
            return false;
        }

        $type = (string) ($fee['auto_apply_type'] ?? '');
        if ($type === '') {
            return true;
        }

        return match ($type) {
            EstablishmentServiceFee::AUTO_PAYMENT => self::matchesPayment($fee, $context),
            EstablishmentServiceFee::AUTO_TIME => self::matchesTimeSlot($fee, $context),
            EstablishmentServiceFee::AUTO_GUEST => self::matchesGuestCount($fee, $context),
            EstablishmentServiceFee::AUTO_DINING => self::matchesDining($fee, $context),
            default => false,
        };
    }

    /**
     * @param  array<string, mixed>  $fee
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function computeFee(array $fee, array $context): array
    {
        $isPercent = (string) ($fee['service_fee_type'] ?? '0') === EstablishmentServiceFee::TYPE_PERCENT;
        $isItem = (string) ($fee['application_type'] ?? '1') === EstablishmentServiceFee::APPLY_ITEM;
        $afterTax = (string) ($fee['calculation_method'] ?? '0') === EstablishmentServiceFee::CALC_AFTER_TAX;
        $rateOrAmount = (float) ($fee['amount'] ?? 0);
        $taxable = (bool) ($fee['taxable'] ?? false);

        $feeAmount = 0.0;
        $taxAmount = 0.0;
        $lineBreakdown = [];
        $lines = is_array($context['lines'] ?? null) ? $context['lines'] : [];

        if ($isItem) {
            foreach ($lines as $index => $line) {
                $qty = (float) ($line['qty'] ?? 0);
                if ($qty <= 0) {
                    $lineBreakdown[] = [
                        'index' => (int) $index,
                        'fee_amount' => 0.0,
                        'tax_amount' => 0.0,
                    ];

                    continue;
                }

                $lineNet = (float) ($line['net'] ?? 0);
                $lineGross = (float) ($line['gross'] ?? 0);
                $lineRate = self::effectiveLineTaxRate($line);

                if ($isPercent) {
                    $base = $afterTax ? $lineGross : $lineNet;
                    $lineFee = $base * ($rateOrAmount / 100);
                } else {
                    $lineFee = $rateOrAmount * $qty;
                }

                $lineFee = max(0, round($lineFee, 2));
                $lineTax = 0.0;
                if ($taxable) {
                    $lineTax = round($lineFee * ($lineRate / 100), 2);
                }

                $feeAmount += $lineFee;
                $taxAmount += $lineTax;
                $lineBreakdown[] = [
                    'index' => (int) $index,
                    'fee_amount' => $lineFee,
                    'tax_amount' => $lineTax,
                ];
            }
        } else {
            if ($isPercent) {
                $base = $afterTax
                    ? (float) ($context['product_total'] ?? 0)
                    : (float) ($context['subtotal_after_discount'] ?? 0);
                $feeAmount = max(0, round($base * ($rateOrAmount / 100), 2));
            } else {
                $feeAmount = max(0, round($rateOrAmount, 2));
            }

            if ($taxable && $feeAmount > 0) {
                $net = (float) ($context['subtotal_after_discount'] ?? 0);
                $vat = (float) ($context['product_vat'] ?? 0);
                $effectiveRate = $net > 0.0001 ? ($vat / $net) : 0.0;
                $taxAmount = round($feeAmount * $effectiveRate, 2);
            }
        }

        $locale = app()->getLocale();

        return [
            'id' => (int) ($fee['id'] ?? 0),
            'name' => $locale === 'ar'
                ? (string) ($fee['name_ar'] ?? $fee['name_en'] ?? '')
                : (string) ($fee['name_en'] ?? $fee['name_ar'] ?? ''),
            'name_ar' => (string) ($fee['name_ar'] ?? ''),
            'name_en' => (string) ($fee['name_en'] ?? ''),
            'fee_amount' => round($feeAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'taxable' => $taxable,
            'service_fee_type' => (string) ($fee['service_fee_type'] ?? '0'),
            'application_type' => (string) ($fee['application_type'] ?? '1'),
            'calculation_method' => (string) ($fee['calculation_method'] ?? '0'),
            'line_breakdown' => $lineBreakdown,
        ];
    }

    /**
     * Line VAT rate as a percent. Prefer the declared rate; fall back to vat / net
     * for tax groups where the select value is not a single percentage.
     *
     * @param  array<string, mixed>  $line
     */
    private static function effectiveLineTaxRate(array $line): float
    {
        $declared = (float) ($line['tax_rate'] ?? 0);
        if ($declared > 0) {
            return $declared;
        }

        $net = (float) ($line['net'] ?? 0);
        $vat = (float) ($line['vat'] ?? 0);
        if ($net <= 0.0001 || $vat <= 0) {
            return 0.0;
        }

        return ($vat / $net) * 100;
    }

    /**
     * @param  array<string, mixed>  $fee
     * @param  array<string, mixed>  $context
     */
    private static function matchesPayment(array $fee, array $context): bool
    {
        $accountId = (int) ($fee['payment_account_id'] ?? 0);
        $cashAccountId = (int) ($context['cash_account_id'] ?? 0);

        return $accountId > 0 && $cashAccountId > 0 && $accountId === $cashAccountId;
    }

    /**
     * @param  array<string, mixed>  $fee
     * @param  array<string, mixed>  $context
     */
    private static function matchesTimeSlot(array $fee, array $context): bool
    {
        $from = $fee['from_date'] ?? null;
        $to = $fee['to_date'] ?? null;
        if (! $from && ! $to) {
            return false;
        }

        try {
            $at = Carbon::parse((string) ($context['transaction_date'] ?? 'now'));
            if ($from && $at->lt(Carbon::parse((string) $from))) {
                return false;
            }
            if ($to && $at->gt(Carbon::parse((string) $to))) {
                return false;
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $fee
     * @param  array<string, mixed>  $context
     */
    private static function matchesGuestCount(array $fee, array $context): bool
    {
        $required = (int) ($fee['guestCount'] ?? $fee['guest_count'] ?? 0);
        $actual = (int) ($context['guest_count'] ?? 0);

        return $required > 0 && $actual >= $required;
    }

    /**
     * @param  array<string, mixed>  $fee
     * @param  array<string, mixed>  $context
     */
    private static function matchesDining(array $fee, array $context): bool
    {
        $ids = array_map('intval', (array) ($fee['dining_type_ids'] ?? []));
        $current = (int) ($context['dining_type_id'] ?? 0);

        return $current > 0 && in_array($current, $ids, true);
    }
}
