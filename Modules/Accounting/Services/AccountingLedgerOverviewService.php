<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountTypes;
use Modules\Accounting\Utils\AccountingUtil;

/**
 * Ledger trend and chart-of-accounts snapshot. Matches /accounting-dashboard math.
 */
class AccountingLedgerOverviewService
{
    /**
     * @param  list<int>  $costCenterIds
     * @return array<string, mixed>
     */
    public function snapshot(Carbon $start, Carbon $end, ?int $costCenterId, array $costCenterIds = []): array
    {
        if (! Schema::hasTable('accounting_accounts_transactions') || ! Schema::hasTable('accounting_accounts')) {
            return [
                'types' => [],
                'monthly' => ['months' => [], 'debit' => [], 'credit' => []],
            ];
        }

        $ids = $costCenterIds !== []
            ? array_values(array_filter(array_map('intval', $costCenterIds)))
            : ($costCenterId ? [$costCenterId] : []);

        return [
            'types' => $this->primaryTypeBalances($start, $end, $ids),
            'monthly' => $this->monthlyDebitCredit($start, $end, $ids),
        ];
    }

    /**
     * @param  list<int>  $costCenterIds
     * @return list<array{id: string, label: string, color: string, balance: float, abs_balance: float}>
     */
    protected function primaryTypeBalances(Carbon $start, Carbon $end, array $costCenterIds): array
    {
        $formula = AccountingUtil::balanceFormula();
        $overview = AccountingAccount::query()
            ->leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($start, $end, $costCenterIds) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereBetween('AAT.operation_date', [$start->toDateString(), $end->toDateString()]);
                if ($costCenterIds !== []) {
                    $join->whereIn('AAT.cost_center_id', $costCenterIds);
                }
            })
            ->select(DB::raw($formula), 'accounting_accounts.account_primary_type')
            ->groupBy('accounting_accounts.account_primary_type')
            ->get()
            ->keyBy('account_primary_type');

        $rows = [];
        foreach (AccountingAccountTypes::accounting_primary_type() as $key => $meta) {
            $balance = (float) ($overview->get($key)->balance ?? 0);
            if ($key === 'liabilities') {
                $balance += (float) ($overview->get('liability')->balance ?? 0);
            }
            $rows[] = [
                'id' => $key,
                'label' => (string) ($meta['label'] ?? $key),
                'color' => (string) ($meta['color'] ?? '#6c757d'),
                'balance' => $balance,
                'abs_balance' => abs($balance),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<int>  $costCenterIds
     * @return array{months: list<string>, debit: list<float>, credit: list<float>}
     */
    protected function monthlyDebitCredit(Carbon $start, Carbon $end, array $costCenterIds): array
    {
        $months = collect();
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();
        while ($cursor->lte($last) && $months->count() < 12) {
            $months->push($cursor->format('Y-m'));
            $cursor->addMonth();
        }

        $q = DB::table('accounting_accounts_transactions')
            ->selectRaw("DATE_FORMAT(operation_date, '%Y-%m') as month_key")
            ->selectRaw('SUM(CASE WHEN type = "debit" THEN amount ELSE 0 END) as debit')
            ->selectRaw('SUM(CASE WHEN type = "credit" THEN amount ELSE 0 END) as credit')
            ->whereBetween('operation_date', [$start->toDateString(), $end->toDateString()])
            ->groupBy('month_key');
        if ($costCenterIds !== []) {
            $q->whereIn('cost_center_id', $costCenterIds);
        }
        $byMonth = $q->get()->keyBy('month_key');

        $debit = [];
        $credit = [];
        foreach ($months as $month) {
            $row = $byMonth->get($month);
            $debit[] = (float) ($row->debit ?? 0);
            $credit[] = (float) ($row->credit ?? 0);
        }

        return [
            'months' => $months->all(),
            'debit' => $debit,
            'credit' => $credit,
        ];
    }
}
