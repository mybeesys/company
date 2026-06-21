<?php

namespace Modules\Accounting\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class AccountingReportDateResolver
{
    /**
     * Default report range: explicit request dates, else span of posted transactions, else current year.
     *
     * @return array{0: string, 1: string}
     */
    public static function range(?Request $request = null, string $startKey = 'start_date', string $endKey = 'end_date'): array
    {
        $start = $request?->input($startKey);
        $end = $request?->input($endKey);

        if (is_string($start) && $start !== '' && is_string($end) && $end !== '') {
            return [$start, $end];
        }

        $bounds = DB::table('accounting_accounts_transactions')
            ->selectRaw('MIN(DATE(operation_date)) as min_date, MAX(DATE(operation_date)) as max_date')
            ->first();

        if ($bounds && ! empty($bounds->min_date) && ! empty($bounds->max_date)) {
            return [(string) $bounds->min_date, (string) $bounds->max_date];
        }

        return [now()->startOfYear()->format('Y-m-d'), now()->format('Y-m-d')];
    }
}
