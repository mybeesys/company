<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodActivityChecker
{
    public static function hasActivity(FiscalPeriod $period): bool
    {
        $start = $period->start_date->toDateString();
        $end = $period->end_date->toDateString();

        return AccountingAccountsTransaction::query()
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end)
            ->exists();
    }
}
