<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

/**
 * Splits a cash-invoice collection debit across payment-method GLs.
 * The returned amounts always sum to the sales-journal collection total.
 */
final class SellCashCollectionSplitter
{
    /**
     * @param  list<array{account_id?: int|string|null, amount?: float|int|string|null}>  $paymentLines
     * @return list<array{account_id: int, amount: float}>
     */
    public static function split(array $paymentLines, int $fallbackAccountId, float $collectionTotal): array
    {
        $collectionTotal = round($collectionTotal, 2);
        if ($collectionTotal <= 0) {
            return [];
        }

        $weights = [];
        foreach ($paymentLines as $line) {
            $amount = round((float) ($line['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $accountId = (int) ($line['account_id'] ?? 0);
            if ($accountId <= 0) {
                $accountId = $fallbackAccountId;
            }
            if ($accountId <= 0) {
                continue;
            }

            $weights[$accountId] = round(($weights[$accountId] ?? 0) + $amount, 2);
        }

        if ($weights === []) {
            if ($fallbackAccountId <= 0) {
                return [];
            }

            return [[
                'account_id' => $fallbackAccountId,
                'amount' => $collectionTotal,
            ]];
        }

        $weightSum = round(array_sum($weights), 2);
        if ($weightSum <= 0) {
            if ($fallbackAccountId <= 0) {
                return [];
            }

            return [[
                'account_id' => $fallbackAccountId,
                'amount' => $collectionTotal,
            ]];
        }

        $out = [];
        $allocated = 0.0;
        $pairs = array_keys($weights);
        $last = count($pairs) - 1;
        foreach ($pairs as $i => $accountId) {
            $weight = $weights[$accountId];
            if ($i === $last) {
                $amount = round($collectionTotal - $allocated, 2);
            } else {
                $amount = round($collectionTotal * ($weight / $weightSum), 2);
                $allocated = round($allocated + $amount, 2);
            }

            if ($amount > 0) {
                $out[] = [
                    'account_id' => (int) $accountId,
                    'amount' => $amount,
                ];
            }
        }

        return $out;
    }
}
