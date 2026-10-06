<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Establishment\Models\EstablishmentServiceFee;
use Modules\General\Models\Transaction;

/**
 * Split collected (customer invoice) vs paid (merchant) service fees.
 */
final class InvoiceServiceFeeAmounts
{
    /**
     * @param  array<string, mixed>  $line
     */
    public static function isPaid(array $line): bool
    {
        return strtoupper((string) ($line['fee_direction'] ?? EstablishmentServiceFee::DIRECTION_COLLECTED))
            === EstablishmentServiceFee::DIRECTION_PAID;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function increasesCustomerTotal(array $line): bool
    {
        if (array_key_exists('increases_customer_total', $line)) {
            return (bool) $line['increases_customer_total'];
        }

        return ! self::isPaid($line);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function showOnInvoice(array $line): bool
    {
        if (array_key_exists('show_on_invoice', $line)) {
            return (bool) $line['show_on_invoice'];
        }

        return self::increasesCustomerTotal($line);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{fee_amount: float, fee_tax: float}
     */
    public static function customerFacing(array $lines): array
    {
        $amount = 0.0;
        $tax = 0.0;
        foreach ($lines as $line) {
            if (! is_array($line) || ! self::increasesCustomerTotal($line)) {
                continue;
            }
            $amount += (float) ($line['fee_amount'] ?? 0);
            $tax += (float) ($line['tax_amount'] ?? 0);
        }

        return [
            'fee_amount' => round($amount, 2),
            'fee_tax' => round($tax, 2),
        ];
    }

    /**
     * Collected fee lines the customer should see on invoices / dashboard.
     *
     * @param  list<array<string, mixed>>|null  $lines
     * @return list<array<string, mixed>>
     */
    public static function visibleOnDocuments(?array $lines): array
    {
        $out = [];
        foreach ($lines ?? [] as $line) {
            if (! is_array($line)) {
                continue;
            }
            if (! self::increasesCustomerTotal($line) || ! self::showOnInvoice($line)) {
                continue;
            }
            if (round((float) ($line['fee_amount'] ?? 0) + (float) ($line['tax_amount'] ?? 0), 2) <= 0) {
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function payloadFromTransaction(?Transaction $transaction): array
    {
        if (! $transaction) {
            return [];
        }
        $payload = $transaction->service_fees_payload;
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        return is_array($payload) ? $payload : [];
    }
};
