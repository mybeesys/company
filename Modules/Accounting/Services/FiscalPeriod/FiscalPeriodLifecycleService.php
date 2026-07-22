<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Exceptions\ClosedFinancialYearException;
use Modules\Accounting\Models\FiscalPeriod;
use Modules\Accounting\Models\FinancialYear;

class FiscalPeriodLifecycleService
{
    public function closePeriod(FiscalPeriod $period): FiscalPeriod
    {
        $period->load('financialYear');

        if (! $period->financialYear?->isOpen()) {
            throw new ClosedFinancialYearException($period->financialYear, $period->start_date);
        }

        FiscalPeriodStatusSync::assertPeriodActionAllowed($period);

        $period->update([
            'status' => FiscalPeriod::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
        ]);

        return $period->fresh();
    }

    public function openPeriod(FiscalPeriod $period): FiscalPeriod
    {
        $period->load('financialYear');

        if (! $period->financialYear?->isOpen()) {
            throw new \InvalidArgumentException(__('accounting::financial_year.reopen_year_first', [
                'year' => $period->financialYear?->name ?? '',
            ]));
        }

        FiscalPeriodStatusSync::assertPeriodActionAllowed($period);

        $period->update([
            'status' => FiscalPeriod::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return $period->fresh();
    }

    public function closeYear(FinancialYear $year, bool $forceWithoutAccountingClose = false): FinancialYear
    {
        if (
            ! $forceWithoutAccountingClose
            && Schema::hasColumn($year->getTable(), 'accounting_closed_at')
            && $year->accounting_closed_at === null
        ) {
            throw new \InvalidArgumentException(__('accounting::fiscal_close.admin_close_requires_accounting_close'));
        }

        $year->periods()->update([
            'status' => FiscalPeriod::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => Auth::id(),
        ]);

        $year->update(['status' => FinancialYear::STATUS_CLOSED]);

        return $year->fresh(['periods']);
    }

    public function openYear(FinancialYear $year): FinancialYear
    {
        $year->periods()->update([
            'status' => FiscalPeriod::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        $year->update(['status' => FinancialYear::STATUS_OPEN]);

        return $year->fresh(['periods']);
    }
}
