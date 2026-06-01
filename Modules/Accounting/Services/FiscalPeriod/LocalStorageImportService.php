<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;

class LocalStorageImportService
{
    public function import(array $payload): int
    {
        $years = $payload['years'] ?? [];
        $count = 0;

        DB::transaction(function () use ($years, &$count) {
            foreach ($years as $index => $row) {
                if (empty($row['start_date']) || empty($row['end_date'])) {
                    continue;
                }

                $year = FinancialYear::query()->create([
                    'name' => $row['description'] ?? ('FY '.($row['end_date'] ?? $index)),
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'status' => in_array($row['status'] ?? 'open', ['open', 'closed', 'closing'], true)
                        ? $row['status']
                        : FinancialYear::STATUS_OPEN,
                    'is_first_year' => $index === 0,
                    'created_by' => Auth::id(),
                ]);

                foreach ($row['periods'] ?? [] as $pIndex => $period) {
                    if (empty($period['start_date']) || empty($period['end_date'])) {
                        continue;
                    }

                    FiscalPeriod::query()->create([
                        'financial_year_id' => $year->id,
                        'name' => $period['name'] ?? ('Period '.($pIndex + 1)),
                        'period_number' => $pIndex + 1,
                        'start_date' => $period['start_date'],
                        'end_date' => $period['end_date'],
                        'status' => $this->normalizePeriodStatus($period['status'] ?? 'open'),
                        'created_by' => Auth::id(),
                    ]);
                }

                $count++;
            }
        });

        return $count;
    }

    private function normalizePeriodStatus(string $status): string
    {
        if (in_array($status, ['closed', 'closing'], true)) {
            return FiscalPeriod::STATUS_CLOSED;
        }

        if ($status === FiscalPeriod::STATUS_UPCOMING) {
            return FiscalPeriod::STATUS_UPCOMING;
        }

        return FiscalPeriod::STATUS_OPEN;
    }
}
