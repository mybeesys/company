<?php

namespace Modules\Accounting\Utils;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JournalEntryValidator
{
    private static function addDecimal(string $left, string $right, int $scale = 2): string
    {
        if (function_exists('bcadd')) {
            return \bcadd($left, $right, $scale);
        }

        $sum = round(((float) $left) + ((float) $right), $scale);

        return number_format($sum, $scale, '.', '');
    }

    private static function compareDecimal(string $left, string $right, int $scale = 2): int
    {
        if (function_exists('bccomp')) {
            return \bccomp($left, $right, $scale);
        }

        $diff = round(((float) $left) - ((float) $right), $scale);

        return $diff < 0 ? -1 : ($diff > 0 ? 1 : 0);
    }

    private static function formatAmount(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    /**
     * @param  array<int, array{type: string, amount: string}>  $normalized
     * @return array{debit: string, credit: string}
     */
    private static function sumNormalizedTotals(array $normalized): array
    {
        $debitTotal = '0.00';
        $creditTotal = '0.00';

        foreach ($normalized as $line) {
            if ($line['type'] === 'debit') {
                $debitTotal = self::addDecimal($debitTotal, $line['amount'], 2);
            } else {
                $creditTotal = self::addDecimal($creditTotal, $line['amount'], 2);
            }
        }

        return ['debit' => $debitTotal, 'credit' => $creditTotal];
    }

    /**
     * @param  array<int, array{type: string, amount: string}>  $normalized
     */
    private static function lastIndexOfType(array $normalized, string $type): ?int
    {
        for ($i = count($normalized) - 1; $i >= 0; $i--) {
            if (($normalized[$i]['type'] ?? '') === $type) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Fix ≤ 0.01 drift after per-line rounding (common with 3-decimal source amounts).
     *
     * @param  array<int, array{type: string, amount: string, account_id: int, cost_center_id: int|null, notes: string|null}>  $normalized
     * @return array<int, array{type: string, amount: string, account_id: int, cost_center_id: int|null, notes: string|null}>
     */
    private static function rebalanceNormalizedLines(array $normalized): array
    {
        $totals = self::sumNormalizedTotals($normalized);
        $diff = function_exists('bcsub')
            ? (float) \bcsub($totals['credit'], $totals['debit'], 2)
            : round((float) $totals['credit'] - (float) $totals['debit'], 2);

        if (abs($diff) < 0.00001) {
            return $normalized;
        }

        if (abs($diff) > 0.01) {
            return $normalized;
        }

        if ($diff > 0) {
            $debitIdx = self::lastIndexOfType($normalized, 'debit');
            if ($debitIdx !== null) {
                $normalized[$debitIdx]['amount'] = self::formatAmount((float) $normalized[$debitIdx]['amount'] + $diff);

                return $normalized;
            }

            $creditIdx = self::lastIndexOfType($normalized, 'credit');
            if ($creditIdx !== null) {
                $normalized[$creditIdx]['amount'] = self::formatAmount((float) $normalized[$creditIdx]['amount'] - $diff);
            }

            return $normalized;
        }

        $debitIdx = self::lastIndexOfType($normalized, 'debit');
        if ($debitIdx !== null) {
            $normalized[$debitIdx]['amount'] = self::formatAmount((float) $normalized[$debitIdx]['amount'] + $diff);

            return $normalized;
        }

        $creditIdx = self::lastIndexOfType($normalized, 'credit');
        if ($creditIdx !== null) {
            $normalized[$creditIdx]['amount'] = self::formatAmount((float) $normalized[$creditIdx]['amount'] - abs($diff));
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{account_id:int,type:string,amount:string,cost_center_id:int|null,notes:string|null}>
     *
     * @throws ValidationException
     */
    public static function validateAndNormalize(array $entries): array
    {
        $normalized = [];
        $rawDebitTotal = 0.0;
        $rawCreditTotal = 0.0;

        foreach (array_values($entries) as $i => $entry) {
            if (! is_array($entry)) {
                throw ValidationException::withMessages([
                    "JournalEntries.$i" => ['Invalid entry row.'],
                ]);
            }

            $v = Validator::make($entry, [
                'account_id' => ['required', 'integer', 'min:1'],
                'debit' => ['nullable', 'numeric', 'gt:0'],
                'credit' => ['nullable', 'numeric', 'gt:0'],
                'cost_center' => ['nullable'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);
            if ($v->fails()) {
                throw new ValidationException($v);
            }

            $debit = (string) ($entry['debit'] ?? '');
            $credit = (string) ($entry['credit'] ?? '');
            $hasDebit = $debit !== '' && (float) $debit > 0;
            $hasCredit = $credit !== '' && (float) $credit > 0;

            if ($hasDebit && $hasCredit) {
                throw ValidationException::withMessages([
                    "JournalEntries.$i" => ['Each row must have either debit or credit (not both).'],
                ]);
            }

            if (! $hasDebit && ! $hasCredit) {
                continue;
            }

            $type = $hasDebit ? 'debit' : 'credit';
            $rawAmount = (float) ($hasDebit ? $debit : $credit);

            if ($type === 'debit') {
                $rawDebitTotal += $rawAmount;
            } else {
                $rawCreditTotal += $rawAmount;
            }

            $costCenterRaw = $entry['cost_center'] ?? null;
            $costCenterId = null;
            if ($costCenterRaw !== null && $costCenterRaw !== '') {
                $costCenterId = (int) $costCenterRaw;
                if ($costCenterId <= 0) {
                    $costCenterId = null;
                }
            }

            $normalized[] = [
                'account_id' => (int) $entry['account_id'],
                'type' => $type,
                'amount' => self::formatAmount($rawAmount),
                'cost_center_id' => $costCenterId,
                'notes' => isset($entry['notes']) ? (string) $entry['notes'] : null,
            ];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages([
                'JournalEntries' => ['Journal entry must contain at least two lines.'],
            ]);
        }

        $balancedRawTotal = self::formatAmount($rawDebitTotal) === self::formatAmount($rawCreditTotal);
        if (! $balancedRawTotal) {
            $totals = self::sumNormalizedTotals($normalized);
            throw ValidationException::withMessages([
                'JournalEntries' => [
                    'Journal entry is not balanced. Debit '.$totals['debit'].' must equal credit '.$totals['credit'].'.',
                ],
            ]);
        }

        $normalized = self::rebalanceNormalizedLines($normalized);

        $totals = self::sumNormalizedTotals($normalized);
        if (self::compareDecimal($totals['debit'], $totals['credit'], 2) !== 0) {
            throw ValidationException::withMessages([
                'JournalEntries' => [
                    'Journal entry is not balanced. Debit '.$totals['debit'].' must equal credit '.$totals['credit'].'.',
                ],
            ]);
        }

        return $normalized;
    }
}
