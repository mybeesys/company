<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;

class FinancialYearService
{
    public function __construct(
        private readonly FiscalPeriodGenerator $generator
    ) {}

    public function create(array $data, bool $autoDates = false): FinancialYear
    {
        return DB::transaction(function () use ($data, $autoDates) {
            $hasYears = FinancialYear::query()->exists();
            $isFirst = ! $hasYears;

            if ($autoDates && $hasYears) {
                [$start, $end] = $this->nextYearDateRange();
                $data['start_date'] = $start;
                $data['end_date'] = $end;
            }

            $start = Carbon::parse($data['start_date'])->toDateString();
            $end = Carbon::parse($data['end_date'])->toDateString();

            $this->assertNoOverlap($start, $end);

            $year = FinancialYear::query()->create([
                'name' => $data['name'] ?? $this->defaultYearName($end),
                'start_date' => $start,
                'end_date' => $end,
                'status' => $data['status'] ?? FinancialYear::STATUS_OPEN,
                'is_first_year' => $isFirst,
                'created_by' => Auth::id(),
            ]);

            $this->syncPeriods($year);

            return $year->load('periods');
        });
    }

    public function update(FinancialYear $year, array $data): FinancialYear
    {
        return DB::transaction(function () use ($year, $data) {
            $datesChanged = false;
            $payload = [];

            if (isset($data['name'])) {
                $payload['name'] = $data['name'];
            }
            if (isset($data['status'])) {
                $payload['status'] = $data['status'];
            }
            if (isset($data['start_date'])) {
                $payload['start_date'] = Carbon::parse($data['start_date'])->toDateString();
                $datesChanged = true;
            }
            if (isset($data['end_date'])) {
                $payload['end_date'] = Carbon::parse($data['end_date'])->toDateString();
                $datesChanged = true;
            }

            if ($datesChanged) {
                $start = $payload['start_date'] ?? $year->start_date->toDateString();
                $end = $payload['end_date'] ?? $year->end_date->toDateString();
                $this->assertNoOverlap($start, $end, $year->id);
            }

            if (! empty($payload)) {
                $year->update($payload);
            }

            if ($datesChanged) {
                $year->periods()->delete();
                $this->syncPeriods($year->fresh());
            }

            return $year->fresh(['periods']);
        });
    }

    public function delete(FinancialYear $year): void
    {
        if (FinancialYearActivityChecker::hasActivity($year)) {
            throw new \InvalidArgumentException(__('accounting::financial_year.cannot_delete_year_with_activity'));
        }

        $year->delete();
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function nextYearDateRange(): array
    {
        $last = FinancialYear::query()->orderByDesc('end_date')->first();
        if (! $last) {
            throw new \RuntimeException('No prior financial year to derive next dates.');
        }

        $start = Carbon::parse($last->end_date)->addDay();
        $end = $start->copy()->addYear()->subDay();

        return [$start->toDateString(), $end->toDateString()];
    }

    public function syncPeriods(FinancialYear $year): void
    {
        $definitions = $this->generator->generate(
            $year->start_date->toDateString(),
            $year->end_date->toDateString()
        );

        foreach ($definitions as $row) {
            FiscalPeriod::query()->create([
                'financial_year_id' => $year->id,
                'name' => $row['name'],
                'period_number' => $row['period_number'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'status' => $row['status'],
                'created_by' => Auth::id(),
            ]);
        }
    }

    private function assertNoOverlap(string $start, string $end, ?int $exceptYearId = null): void
    {
        $overlap = FinancialYear::query()
            ->when($exceptYearId, fn ($q) => $q->where('id', '!=', $exceptYearId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->exists();

        if ($overlap) {
            throw new \InvalidArgumentException(__('accounting::financial_year.year_dates_overlap'));
        }
    }

    private function defaultYearName(string $endDate): string
    {
        $year = Carbon::parse($endDate)->year;

        return app()->getLocale() === 'ar'
            ? 'السنة المالية '.$year
            : 'Fiscal year '.$year;
    }
}
