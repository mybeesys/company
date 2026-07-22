<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodCloseReadinessChecker
{
    public function __construct(
        private readonly FiscalCloseRoutingResolver $routing,
    ) {}

    /**
     * @return array{
     *     routing: array,
     *     routing_complete: bool,
     *     routing_errors: list<string>,
     *     year_open: bool,
     *     all_periods_closed: bool,
     *     open_periods: list<array{id: int, name: string}>,
     *     is_year_end_boundary: bool,
     *     is_remedial: bool,
     *     accounting_close_posted: bool,
     *     can_preview: bool,
     *     blocking_messages: list<string>,
     *     warnings: list<string>
     * }
     */
    public function check(FinancialYear $year, ?FiscalPeriod $closingPeriod = null): array
    {
        $year->loadMissing('periods');

        $routingStatus = $this->routing->status();
        $routingErrors = $this->routing->validationErrors();
        $isYearEnd = $this->isYearEndBoundary($year, $closingPeriod);
        $accountingPosted = $this->hasAccountingClosePosted($year);
        $yearOpen = $year->isOpen();
        $isRemedial = ! $yearOpen && ! $accountingPosted;

        $openPeriods = $year->periods
            ->filter(function (FiscalPeriod $period) use ($closingPeriod) {
                if ($period->status !== FiscalPeriod::STATUS_OPEN) {
                    return false;
                }

                if ($closingPeriod !== null && (int) $period->id === (int) $closingPeriod->id) {
                    return false;
                }

                return true;
            })
            ->map(fn (FiscalPeriod $period) => [
                'id' => (int) $period->id,
                'name' => (string) $period->name,
            ])
            ->values()
            ->all();

        $blocking = [];
        $warnings = [];

        if ($isRemedial) {
            $warnings[] = __('accounting::fiscal_close.remedial_mode');
        } elseif (! $yearOpen && $accountingPosted) {
            $warnings[] = __('accounting::fiscal_close.year_admin_closed');
        }

        if (! $routingStatus['complete']) {
            $blocking[] = __('accounting::fiscal_close.routing_incomplete');
        }

        foreach ($routingErrors as $error) {
            $blocking[] = $error;
        }

        if ($openPeriods !== []) {
            $warnings[] = __('accounting::fiscal_close.open_periods_remain', ['count' => count($openPeriods)]);
        }

        if ($accountingPosted) {
            $warnings[] = __('accounting::fiscal_close.already_posted');
        }

        if (! $isYearEnd) {
            $warnings[] = __('accounting::fiscal_close.not_year_end_boundary');
        }

        $canPreview = $blocking === [] && $isYearEnd;

        return [
            'routing' => $routingStatus,
            'routing_complete' => $routingStatus['complete'] && $routingErrors === [],
            'routing_errors' => $routingErrors,
            'year_open' => $yearOpen,
            'all_periods_closed' => $openPeriods === [],
            'open_periods' => $openPeriods,
            'is_year_end_boundary' => $isYearEnd,
            'is_remedial' => $isRemedial,
            'accounting_close_posted' => $accountingPosted,
            'can_preview' => $canPreview,
            'blocking_messages' => array_values(array_unique($blocking)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function hasAccountingClosePosted(FinancialYear $year): bool
    {
        if (! Schema::hasColumn($year->getTable(), 'accounting_closed_at')) {
            return false;
        }

        return $year->accounting_closed_at !== null;
    }

    public function isYearEndBoundary(FinancialYear $year, ?FiscalPeriod $period = null): bool
    {
        $year->loadMissing('periods');

        if ($period === null) {
            return true;
        }

        if ($year->periods->isEmpty()) {
            return true;
        }

        $lastPeriod = $year->periods->sortByDesc(function (FiscalPeriod $item) {
            return sprintf(
                '%s-%05d',
                $item->end_date?->toDateString() ?? '',
                (int) ($item->period_number ?? 0),
            );
        })->first();

        return $lastPeriod !== null && (int) $lastPeriod->id === (int) $period->id;
    }
}
