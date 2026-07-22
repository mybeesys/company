<?php

namespace Modules\Accounting\Services\FiscalPeriod;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\FinancialYear;

class FiscalPeriodPlBalanceCalculator
{
    /**
     * @return Collection<int, object{
     *     id: int,
     *     gl_code: string,
     *     name_ar: string,
     *     name_en: string,
     *     account_type: string,
     *     account_primary_type: string,
     *     debit_balance: float,
     *     credit_balance: float,
     *     signed_balance: float,
     *     is_income: bool
     * }>
     */
    public function accountsWithBalances(FinancialYear $year): Collection
    {
        $start = $year->start_date->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $year->end_date->copy()->endOfDay()->format('Y-m-d H:i:s');

        // Include any P&L account with direct postings in the year — including parents
        // that received journal lines (common with imported charts). Leaf-only filtering
        // left residual expense/income balances unclosed.
        return AccountingAccount::query()
            ->join('accounting_accounts_transactions as AAT', 'AAT.accounting_account_id', '=', 'accounting_accounts.id')
            ->whereBetween('AAT.operation_date', [$start, $end])
            ->where(function ($query) {
                $query->whereIn('accounting_accounts.account_type', ['income', 'expenses', 'expense'])
                    ->orWhereIn('accounting_accounts.account_primary_type', ['income', 'expenses', 'expense'])
                    ->orWhereRaw("LEFT(REPLACE(accounting_accounts.gl_code, '.', ''), 1) IN ('4', '5')");
            })
            ->where('accounting_accounts.status', 'active')
            ->groupBy(
                'accounting_accounts.id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type',
                'accounting_accounts.account_primary_type',
            )
            ->select(
                'accounting_accounts.id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_type',
                'accounting_accounts.account_primary_type',
                DB::raw("SUM(IF(AAT.type = 'credit', AAT.amount, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit', AAT.amount, 0)) as debit_balance"),
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get()
            ->map(function ($account) {
                $isIncome = $this->isIncomeAccount($account);

                $debit = (float) $account->debit_balance;
                $credit = (float) $account->credit_balance;
                $signed = $isIncome ? ($credit - $debit) : ($debit - $credit);

                $account->is_income = $isIncome;
                $account->signed_balance = round($signed, 2);
                $account->debit_balance = $debit;
                $account->credit_balance = $credit;

                return $account;
            })
            ->filter(fn ($account) => abs((float) $account->signed_balance) > 0.0001)
            ->values();
    }

    public function totals(FinancialYear $year): array
    {
        $accounts = $this->accountsWithBalances($year);

        $totalIncome = round($accounts->where('is_income', true)->sum('signed_balance'), 2);
        $totalExpenses = round($accounts->where('is_income', false)->sum('signed_balance'), 2);
        $netIncome = round($totalIncome - $totalExpenses, 2);

        return [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'pl_accounts_count' => $accounts->count(),
        ];
    }

    public function isIncomeAccount(object $account): bool
    {
        $type = strtolower((string) ($account->account_type ?? ''));
        $primary = strtolower((string) ($account->account_primary_type ?? ''));

        if ($type === 'income' || $primary === 'income') {
            return true;
        }

        if (in_array($type, ['expenses', 'expense'], true) || in_array($primary, ['expenses', 'expense'], true)) {
            return false;
        }

        $digit = $this->leadingGlDigit((string) ($account->gl_code ?? ''));

        return $digit === '4';
    }

    public function leadingGlDigit(string $glCode): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $glCode) ?? '';

        return $normalized !== '' ? $normalized[0] : '';
    }
}
