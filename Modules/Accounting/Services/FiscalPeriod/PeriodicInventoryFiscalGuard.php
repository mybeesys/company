<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use DateTimeInterface;
use Modules\Accounting\Exceptions\ClosedFinancialYearException;
use Modules\Accounting\Exceptions\ClosedFiscalPeriodException;
use Modules\Accounting\Exceptions\FinancialYearNotFoundException;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\PeriodicInventory;

/**
 * Periodic inventory must respect fiscal years/periods whenever they exist in the database,
 * even if global period locking is disabled in settings.
 */
final class PeriodicInventoryFiscalGuard
{
    public static function assertPostable(DateTimeInterface|string $operationDate): void
    {
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

    public static function assertInventoryPeriodPostable(PeriodicInventory $inventory): void
    {
        self::assertPostable($inventory->end_date ?? now());

        if ($inventory->start_date) {
            self::assertPostable($inventory->start_date);
        }
    }
}
