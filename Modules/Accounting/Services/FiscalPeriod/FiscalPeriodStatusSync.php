<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodStatusSync
{
    /**
     * Promote periods whose start date has arrived but were still marked upcoming at creation time.
     */
    public static function promoteStartedPeriods(?int $financialYearId = null): void
    {
        $query = FiscalPeriod::query()
            ->where('status', FiscalPeriod::STATUS_UPCOMING)
            ->whereDate('start_date', '<=', Carbon::today());

        if ($financialYearId !== null) {
            $query->where('financial_year_id', $financialYearId);
        }

        $query->update(['status' => FiscalPeriod::STATUS_OPEN]);
    }

    public static function assertPeriodActionAllowed(FiscalPeriod $period): void
    {
        self::promoteStartedPeriods($period->financial_year_id);
        $period->refresh();

        if (Carbon::today()->lt($period->start_date)) {
            throw new \InvalidArgumentException(__('accounting::financial_year.period_action_disabled'));
        }
    }
}
