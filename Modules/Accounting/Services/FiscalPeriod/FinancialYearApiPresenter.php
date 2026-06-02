<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Services\FiscalPeriod\FinancialYearActivityChecker;

class FinancialYearApiPresenter
{
    public static function year(FinancialYear $year): array
    {
        $year->loadMissing('periods');

        return [
            'id' => $year->id,
            'start_date' => $year->start_date->toDateString(),
            'end_date' => $year->end_date->toDateString(),
            'description' => $year->name,
            'name' => $year->name,
            'status' => $year->status,
            'is_first_year' => (bool) $year->is_first_year,
            'has_activity' => FinancialYearActivityChecker::hasActivity($year),
            'created_at' => $year->created_at?->toIso8601String(),
            'periods' => $year->periods->map(fn (FiscalPeriod $p) => self::period($p))->values()->all(),
        ];
    }

    public static function period(FiscalPeriod $period): array
    {
        return [
            'id' => $period->id,
            'name' => $period->name,
            'start_date' => $period->start_date->toDateString(),
            'end_date' => $period->end_date->toDateString(),
            'status' => $period->status,
            'period_number' => $period->period_number,
        ];
    }
}
