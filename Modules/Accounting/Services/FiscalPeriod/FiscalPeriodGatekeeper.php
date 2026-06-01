<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use DateTimeInterface;
use Modules\Accounting\Exceptions\ClosedFinancialYearException;
use Modules\Accounting\Exceptions\ClosedFiscalPeriodException;
use Modules\Accounting\Exceptions\FinancialYearNotFoundException;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\General\Models\Setting;

class FiscalPeriodGatekeeper
{
    /**
     * Accounting gatekeeper: ensure operation_date is inside an open fiscal period.
     * No-op when locking disabled or no financial years exist (soft fallback).
     */
    public static function assertPostable(DateTimeInterface|string $operationDate): void
    {
        if (! Setting::isFinancialPeriodLockingEnabled()) {
            return;
        }

        if (! FinancialYear::query()->exists()) {
            return;
        }

        $date = Carbon::parse($operationDate)->startOfDay();

        $year = FiscalPeriodResolver::findYearForDate($date);
        if (! $year) {
            throw new FinancialYearNotFoundException($date);
        }

        if (! $year->isOpen()) {
            throw new ClosedFinancialYearException($year, $date);
        }

        FiscalPeriodStatusSync::promoteStartedPeriods($year->id);

        $period = FiscalPeriodResolver::findPeriodForDate($year, $date);
        if (! $period || $period->status !== FiscalPeriod::STATUS_OPEN) {
            throw new ClosedFiscalPeriodException($period, $date);
        }
    }
}
