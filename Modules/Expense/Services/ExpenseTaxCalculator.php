<?php

namespace Modules\Expense\Services;

use Modules\General\Models\Tax;

final class ExpenseTaxCalculator
{
    /**
     * Effective VAT percent from General settings: single tax uses {@see Tax::$amount};
     * tax groups sum linked sub-taxes.
     */
    public static function effectivePercent(Tax $tax): float
    {
        if ((int) $tax->is_tax_group === 1) {
            $tax->loadMissing('sub_taxes');

            return round((float) $tax->sub_taxes->sum(fn ($t) => (float) $t->amount), 6);
        }

        return (float) $tax->amount;
    }

    /**
     * Tax-inclusive total: extract VAT using summed percent(s).
     *
     * @return array{tax: float, net: float}
     */
    public static function extractTaxFromInclusiveTotal(float $grossAmount, float $percentSum): array
    {
        if ($percentSum <= 0) {
            return ['tax' => 0.0, 'net' => round($grossAmount, 6)];
        }

        $tax = $grossAmount - ($grossAmount / (1 + ($percentSum / 100)));

        return [
            'tax' => round($tax, 6),
            'net' => round($grossAmount - $tax, 6),
        ];
    }

    /**
     * Tax-exclusive net: VAT is added on top (net × rate).
     *
     * @return array{tax: float, gross: float}
     */
    public static function computeTaxFromExclusiveNet(float $netAmount, float $percentSum): array
    {
        if ($percentSum <= 0) {
            return ['tax' => 0.0, 'gross' => round($netAmount, 6)];
        }

        $tax = $netAmount * ($percentSum / 100);

        return [
            'tax' => round($tax, 6),
            'gross' => round($netAmount + $tax, 6),
        ];
    }

    public static function taxSnapshot(Tax $tax, string $basis): array
    {
        $tax->loadMissing('sub_taxes');

        return [
            'tax_id' => $tax->id,
            'name' => $tax->name,
            'name_en' => $tax->name_en ?? null,
            'percent' => static::effectivePercent($tax),
            'is_tax_group' => (bool) $tax->is_tax_group,
            'basis' => $basis,
        ];
    }
}
