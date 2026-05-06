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

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array{account_id:int,type:string,amount:string,cost_center_id:int|null,notes:string|null}>
     *
     * @throws ValidationException
     */
    public static function validateAndNormalize(array $entries): array
    {
        $normalized = [];
        $debitTotal = '0.00';
        $creditTotal = '0.00';

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
            $amount = $hasDebit ? $debit : $credit;

            // Saudi Riyal: 2-decimal money; keep as string for DB decimal columns.
            $amount = number_format((float) $amount, 2, '.', '');

            if ($type === 'debit') {
                $debitTotal = self::addDecimal($debitTotal, $amount, 2);
            } else {
                $creditTotal = self::addDecimal($creditTotal, $amount, 2);
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
                'amount' => $amount,
                'cost_center_id' => $costCenterId,
                'notes' => isset($entry['notes']) ? (string) $entry['notes'] : null,
            ];
        }

        if (count($normalized) < 2) {
            throw ValidationException::withMessages([
                'JournalEntries' => ['Journal entry must contain at least two lines.'],
            ]);
        }

        if (self::compareDecimal($debitTotal, $creditTotal, 2) !== 0) {
            throw ValidationException::withMessages([
                'JournalEntries' => ["Journal entry is not balanced. Debit $debitTotal must equal credit $creditTotal."],
            ]);
        }

        return $normalized;
    }
}
