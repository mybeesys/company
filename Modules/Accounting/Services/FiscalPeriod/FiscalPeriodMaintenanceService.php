<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\FiscalPeriod;

class FiscalPeriodMaintenanceService
{
    public function update(FiscalPeriod $period, array $data): FiscalPeriod
    {
        return DB::transaction(function () use ($period, $data) {
            $period->loadMissing('financialYear');
            $year = $period->financialYear;

            $start = isset($data['start_date'])
                ? Carbon::parse($data['start_date'])->toDateString()
                : $period->start_date->toDateString();
            $end = isset($data['end_date'])
                ? Carbon::parse($data['end_date'])->toDateString()
                : $period->end_date->toDateString();

            if ($end < $start) {
                throw new \InvalidArgumentException(__('accounting::financial_year.validation_end_before_start'));
            }

            $yearStart = $year->start_date->toDateString();
            $yearEnd = $year->end_date->toDateString();

            if ($start < $yearStart || $end > $yearEnd) {
                throw new \InvalidArgumentException(__('accounting::financial_year.period_outside_year'));
            }

            $this->assertNoOverlap($year->id, $start, $end, $period->id);

            $payload = [];
            if (array_key_exists('name', $data)) {
                $payload['name'] = $data['name'];
            }
            if (isset($data['start_date'])) {
                $payload['start_date'] = $start;
            }
            if (isset($data['end_date'])) {
                $payload['end_date'] = $end;
            }
            if (isset($data['status'])) {
                $payload['status'] = $data['status'];
            }

            if ($payload !== []) {
                $period->update($payload);
            }

            return $period->fresh();
        });
    }

    public function delete(FiscalPeriod $period): void
    {
        if (FiscalPeriodActivityChecker::hasActivity($period)) {
            throw new \InvalidArgumentException(__('accounting::financial_year.cannot_delete_period_with_activity'));
        }

        $period->delete();
    }

    private function assertNoOverlap(int $yearId, string $start, string $end, ?int $exceptId = null): void
    {
        $overlap = FiscalPeriod::query()
            ->where('financial_year_id', $yearId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->exists();

        if ($overlap) {
            throw new \InvalidArgumentException(__('accounting::financial_year.period_dates_overlap'));
        }
    }
}
