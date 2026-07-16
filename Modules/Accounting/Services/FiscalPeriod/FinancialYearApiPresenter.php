<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;

class FinancialYearApiPresenter
{
    public static function year(FinancialYear $year): array
    {
        $year->loadMissing('periods');

        $payload = [
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

        if (Schema::hasColumn($year->getTable(), 'accounting_closed_at')) {
            $payload['accounting_closed_at'] = $year->accounting_closed_at?->toIso8601String();
            $payload['accounting_close_journal_id'] = $year->accounting_close_journal_id
                ? (int) $year->accounting_close_journal_id
                : null;
            $payload['accounting_close_posted'] = $year->accounting_closed_at !== null;
        }

        return $payload;
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
