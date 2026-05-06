<?php

namespace Modules\Accounting\Utils;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AutoJournalGuard
{
    private const MONEY_EPSILON = 0.005;

    /**
     * Ensure an accounting mapping is balanced (debit == credit) at 2 decimals.
     */
    public static function assertBalanced(int $accTransMappingId): void
    {
        $rows = DB::table('accounting_accounts_transactions')
            ->selectRaw('type, ROUND(SUM(amount), 2) as total')
            ->where('acc_trans_mapping_id', $accTransMappingId)
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $debit = number_format((float) ($rows['debit'] ?? 0), 2, '.', '');
        $credit = number_format((float) ($rows['credit'] ?? 0), 2, '.', '');

        if (self::compareMoney($debit, $credit) !== 0) {
            throw new RuntimeException("Auto journal is not balanced for mapping {$accTransMappingId}: debit {$debit} != credit {$credit}");
        }
    }

    private static function compareMoney(string $left, string $right): int
    {
        if (function_exists('bccomp')) {
            return bccomp($left, $right, 2);
        }

        $diff = (float) $left - (float) $right;
        if (abs($diff) < self::MONEY_EPSILON) {
            return 0;
        }

        return $diff > 0 ? 1 : -1;
    }
}
