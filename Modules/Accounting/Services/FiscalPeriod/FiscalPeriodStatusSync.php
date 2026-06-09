<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodStatusSync
{
    /**
     * Promote future periods (stored closed/upcoming) to open when their start date arrives.
     */
    public static function promoteStartedPeriods(?int $financialYearId = null): void
    {
        $query = FiscalPeriod::query()
            ->whereDate('start_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->where('status', FiscalPeriod::STATUS_UPCOMING)
                    ->orWhere(function ($q2) {
                        $q2->where('status', FiscalPeriod::STATUS_CLOSED)
                            ->whereNull('closed_at')
                            ->whereNull('closed_by');
                    });
            });

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
