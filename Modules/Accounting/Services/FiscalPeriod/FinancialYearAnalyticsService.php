<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\FinancialYear;
use Modules\Accounting\Models\FiscalPeriod;

/**
 * Read-only analytics for fiscal year / period reports (operation_date filtering only).
 */
class FinancialYearAnalyticsService
{
    public const BREAKDOWN_KEYS = [
        'journal_entry',
        'sell',
        'purchases',
        'receipt_voucher',
        'payment_voucher',
        'expense',
        'inventory_adjustment',
        'other',
    ];

    /** @var array<string, list<string>> */
    private const SUB_TYPE_GROUPS = [
        'sell' => ['sell', 'sell-return', 'sell_cash', 'sales_revenue'],
        'purchases' => ['purchases', 'purchases-return'],
        'receipt_voucher' => ['receipt_voucher'],
        'payment_voucher' => ['payment_voucher'],
        'expense' => ['expense', 'expense_refund'],
        'inventory_adjustment' => ['inventory_adjustment', 'periodic_inventory_adjustment'],
    ];

    public function forYear(FinancialYear $year, ?int $currentYearId = null): array
    {
        $year->loadMissing('periods');

        return $this->buildPayload(
            $year,
            $year->start_date->toDateString(),
            $year->end_date->toDateString(),
            $year->periods,
            $currentYearId,
            null
        );
    }

    public function forPeriod(FiscalPeriod $period, ?int $currentYearId = null): array
    {
        $period->loadMissing('financialYear');
        $year = $period->financialYear;

        return $this->buildPayload(
            $year,
            $period->start_date->toDateString(),
            $period->end_date->toDateString(),
            collect([$period]),
            $currentYearId,
            $period
        );
    }

    /**
     * @param  Collection<int, FiscalPeriod>  $linkedPeriods
     */
    private function buildPayload(
        FinancialYear $year,
        string $start,
        string $end,
        Collection $linkedPeriods,
        ?int $currentYearId,
        ?FiscalPeriod $focusPeriod
    ): array {
        $mappingBase = AccountingAccTransMapping::query()
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end);

        $journalCount = (clone $mappingBase)->count();

        $txnBase = AccountingAccountsTransaction::query()
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end);

        $totalDebit = (float) (clone $txnBase)->where('type', 'debit')->sum('amount');
        $totalCredit = (float) (clone $txnBase)->where('type', 'credit')->sum('amount');

        $breakdown = $this->transactionBreakdown($start, $end);
        $monthly = $this->monthlyActivity($start, $end);
        $recent = $this->recentEntries($start, $end, 12);

        return [
            'year' => $year,
            'focus_period' => $focusPeriod,
            'range' => ['start' => $start, 'end' => $end],
            'is_current_year' => $currentYearId !== null && (int) $year->id === (int) $currentYearId,
            'summary' => [
                'journal_count' => $journalCount,
                'operations_count' => $journalCount,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'periods_count' => $linkedPeriods->count(),
                'status' => $focusPeriod ? $focusPeriod->status : $year->status,
            ],
            'breakdown' => $breakdown,
            'monthly' => $monthly,
            'linked_periods' => $linkedPeriods->sortBy('period_number')->values(),
            'recent_entries' => $recent,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function transactionBreakdown(string $start, string $end): array
    {
        $out = array_fill_keys(self::BREAKDOWN_KEYS, 0);

        $mappings = AccountingAccTransMapping::query()
            ->with(['transactions:id,acc_trans_mapping_id,sub_type'])
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end)
            ->get(['id', 'type', 'is_manual']);

        foreach ($mappings as $mapping) {
            if ($mapping->type === 'journal_entry') {
                $out['journal_entry']++;
                continue;
            }

            $sub = $mapping->transactions->pluck('sub_type')->filter()->first();
            $key = self::resolveCategory($sub);
            $out[$key] = ($out[$key] ?? 0) + 1;
        }

        return $out;
    }

    /**
     * @return list<array{month: string, label: string, count: int}>
     */
    private function monthlyActivity(string $start, string $end): array
    {
        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', operation_date)"
            : "DATE_FORMAT(operation_date, '%Y-%m')";

        $rows = AccountingAccTransMapping::query()
            ->selectRaw("{$monthExpr} as ym, COUNT(*) as cnt")
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $locale = app()->getLocale();

        return $rows->map(function ($row) use ($locale) {
            $ym = (string) $row->ym;
            $label = $ym;
            try {
                $label = Carbon::createFromFormat('Y-m', $ym)->locale($locale)->translatedFormat('F Y');
            } catch (\Throwable) {
                // keep ym
            }

            return [
                'month' => $ym,
                'label' => $label,
                'count' => (int) $row->cnt,
            ];
        })->values()->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, AccountingAccTransMapping>
     */
    private function recentEntries(string $start, string $end, int $limit): Collection
    {
        return AccountingAccTransMapping::query()
            ->with(['transactions' => function ($q) {
                $q->join('accounting_accounts', 'accounting_accounts.id', '=', 'accounting_accounts_transactions.accounting_account_id')
                    ->select(
                        'accounting_accounts_transactions.*',
                        'accounting_accounts.name_ar',
                        'accounting_accounts.name_en',
                        'accounting_accounts.gl_code'
                    );
            }])
            ->whereDate('operation_date', '>=', $start)
            ->whereDate('operation_date', '<=', $end)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public static function resolveCategory(?string $subType): string
    {
        $sub = strtolower(trim((string) $subType));

        if ($sub === 'journal_entry') {
            return 'journal_entry';
        }

        foreach (self::SUB_TYPE_GROUPS as $key => $types) {
            if (in_array($sub, $types, true)) {
                return $key;
            }
        }

        return 'other';
    }
}
