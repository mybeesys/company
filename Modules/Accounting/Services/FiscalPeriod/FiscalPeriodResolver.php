<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodResolver
{
    public static function findYearForDate(Carbon $date): ?FinancialYear
    {
        return FinancialYear::query()
            ->containingDate($date)
            ->orderBy('start_date')
            ->first();
    }

    public static function findPeriodForDate(FinancialYear $year, Carbon $date): ?FiscalPeriod
    {
        return FiscalPeriod::query()
            ->where('financial_year_id', $year->id)
            ->containingDate($date)
            ->first();
    }
}
