<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodGenerator
{
    /**
     * @return Collection<int, array{name: string, period_number: int, start_date: string, end_date: string, status: string}>
     */
    public function generate(string $startDate, string $endDate): Collection
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $today = Carbon::today();
        $periods = collect();
        $cursor = $start->copy();
        $index = 1;

        while ($cursor->lte($end)) {
            $periodStart = $cursor->copy();
            $periodEnd = $periodStart->copy()->endOfMonth();
            if ($periodEnd->gt($end)) {
                $periodEnd = $end->copy();
            }

            $status = FiscalPeriod::STATUS_OPEN;
            if ($periodStart->gt($today)) {
                $status = FiscalPeriod::STATUS_UPCOMING;
            }

            $periods->push([
                'name' => $this->periodName($periodStart),
                'period_number' => $index,
                'start_date' => $periodStart->toDateString(),
                'end_date' => $periodEnd->toDateString(),
                'status' => $status,
            ]);

            $index++;
            $cursor = $periodEnd->copy()->addDay();
        }

        return $periods;
    }

    private function periodName(Carbon $date): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return $date->locale('ar')->translatedFormat('F Y');
        }

        return $date->locale('en')->translatedFormat('F Y');
    }
}
