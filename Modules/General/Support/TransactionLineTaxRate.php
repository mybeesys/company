<?php

declare(strict_types=1);

namespace Modules\General\Support;

use Illuminate\Support\Facades\Schema;
use Modules\General\Models\Tax;

/**
 * transaction_sell_lines.tax_id stores mixed values:
 * - Web sales: VAT percentage (e.g. 15) from the tax_vat select.
 * - POS sales: taxes.id (e.g. 45) until normalized on save.
 */
final class TransactionLineTaxRate
{
    public static function displayPercent(?string $storedTaxId): string
    {
        if ($storedTaxId === null || $storedTaxId === '') {
            return '--';
        }

        $tax = self::findTaxRecord($storedTaxId);
        if ($tax !== null) {
            return (string) ($tax->amount ?? $storedTaxId);
        }

        return (string) $storedTaxId;
    }

    public static function normalizeForStorage(mixed $taxIdFromPayload): ?string
    {
        if ($taxIdFromPayload === null || $taxIdFromPayload === '') {
            return null;
        }

        $tax = self::findTaxRecord($taxIdFromPayload);
        if ($tax !== null) {
            return (string) ($tax->amount ?? $taxIdFromPayload);
        }

        return (string) $taxIdFromPayload;
    }

    private static function findTaxRecord(mixed $id): ?Tax
    {
        if (! Schema::hasTable('taxes')) {
            return null;
        }

        try {
            return Tax::query()->find($id);
        } catch (\Throwable) {
            return null;
        }
    }
}
