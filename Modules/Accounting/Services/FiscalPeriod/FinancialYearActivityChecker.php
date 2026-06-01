<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\FinancialYear;

class FinancialYearActivityChecker
{
    public static function hasActivity(FinancialYear $year): bool
    {
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();

        return AccountingAccountsTransaction::query()
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end)
            ->exists();
    }
}
