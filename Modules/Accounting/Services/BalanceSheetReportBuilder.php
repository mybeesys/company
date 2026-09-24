<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Services\AccountCoaVisibility;

/**
 * Balance sheet from the COA tree (assets / liabilities / equity) as-is.
 * Income & expense accounts are not listed line-by-line; their net is plugged
 * into Current Year Profit / Loss (32201) so A = L + E stays balanced.
 */
final class BalanceSheetReportBuilder
{
    /**
     * @param  list<int|string>  $chooseCostCenterSelect
     * @return array<string, mixed>
     */
    public function build(
        string $endDate,
        array $chooseCostCenterSelect,
        int $withZeroBalances,
        mixed $levelFilter,
        callable $roundMoney,
        callable $filterByLevel,
        callable $limitSectionsByLevel,
    ): array {
        $costCenterIds = array_values(array_filter($chooseCostCenterSelect));

        $debitMinusCredit = '(
            COALESCE(SUM(CASE WHEN AAT.type = \'debit\' THEN AAT.amount ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN AAT.type = \'credit\' THEN AAT.amount ELSE 0 END), 0)
        )';

        $balanceExpression = "($debitMinusCredit) * CASE
            WHEN accounting_accounts.account_primary_type = 'asset' THEN 1
            WHEN accounting_accounts.account_primary_type IN ('liability', 'liabilities', 'equity') THEN -1
            WHEN accounting_accounts.account_primary_type = 'income' THEN -1
            WHEN accounting_accounts.account_primary_type IN ('expenses', 'expense') THEN 1
            ELSE 0
        END";

        $allRows = AccountingAccount::query()
            ->leftJoin('accounting_accounts_transactions as AAT', function ($join) use ($endDate, $costCenterIds) {
                $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                    ->whereDate('AAT.operation_date', '<=', $endDate);
                if ($costCenterIds !== []) {
                    $join->whereIn('AAT.cost_center_id', $costCenterIds);
                }
            })
            ->leftJoin('accounting_account_types as acc_subtype', 'acc_subtype.id', '=', 'accounting_accounts.account_sub_type_id')
            ->where(function ($q) {
                $q->whereIn('accounting_accounts.account_primary_type', [
                    'asset', 'liability', 'liabilities', 'equity', 'income', 'expenses', 'expense',
                ])->orWhereRaw("LEFT(REGEXP_REPLACE(accounting_accounts.gl_code, '[^0-9]', ''), 1) IN ('1','2','3','4','5')");
            })
            ->groupBy(
                'accounting_accounts.id',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.account_type',
                'accounting_accounts.gl_code',
                'acc_subtype.name_en',
                'acc_subtype.name_ar',
            )
            ->select(
                'accounting_accounts.id',
                'accounting_accounts.parent_account_id',
                'accounting_accounts.name_ar',
                'accounting_accounts.name_en',
                'accounting_accounts.account_primary_type',
                'accounting_accounts.account_type',
                'accounting_accounts.gl_code',
                'acc_subtype.name_en as account_sub_type_name_en',
                'acc_subtype.name_ar as account_sub_type_name_ar',
                DB::raw($balanceExpression.' as balance')
            )
            ->orderBy('accounting_accounts.gl_code')
            ->get()
            ->map(function ($row) use ($roundMoney) {
                $row->balance = $roundMoney($row->balance ?? 0);

                return $row;
            });

        $plAccounts = $allRows->filter(fn ($row) => $this->isPlAccount($row))->values();
        $accounts = $allRows->reject(fn ($row) => $this->isPlAccount($row))->values();

        $plNet = $roundMoney(
            $plAccounts->filter(fn ($row) => $this->isIncomeAccount($row))->sum('balance')
            - $plAccounts->reject(fn ($row) => $this->isIncomeAccount($row))->sum('balance')
        );

        $accounts = $this->enrichTree($accounts);
        $accounts = $this->applyCurrentYearProfitPlug($accounts, $plNet, $roundMoney);
        $accounts = AccountCoaVisibility::rollupHiddenIntoParents($accounts);

        if ($withZeroBalances === 0) {
            $accounts = $this->keepNonZeroTree($accounts);
        }

        $assets = $accounts->filter(fn ($a) => $this->isAsset($a))->values();
        $liabilities = $accounts->filter(fn ($a) => $this->isLiability($a))->values();
        $equities = $accounts->filter(fn ($a) => $this->isEquity($a) && ! $this->isPartnersCurrentLiability($a))->values();

        $totalAssets = $roundMoney($assets->sum('balance'));
        $totalLiabilities = $roundMoney($liabilities->sum('balance'));
        $totalEquity = $roundMoney($equities->sum('balance'));
        $totalLiabOwners = $roundMoney($totalLiabilities + $totalEquity);
        $difference = $roundMoney(abs($totalAssets - $totalLiabOwners));

        $metrics = $this->metrics($accounts, $totalAssets, $totalLiabilities, $totalEquity, $roundMoney);
        $sections = $limitSectionsByLevel(
            $this->buildSections($accounts, $roundMoney),
            $levelFilter
        );

        return [
            'accounts' => $filterByLevel($accounts, $levelFilter),
            'assets' => $filterByLevel($assets, $levelFilter),
            'liabilities' => $filterByLevel($liabilities, $levelFilter),
            'equities' => $filterByLevel($equities, $levelFilter),
            'sections' => $sections,
            'metrics' => $metrics,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liab_owners' => $totalLiabOwners,
            'difference' => $difference,
            'balance_status' => $difference < 0.005
                ? __('accounting::lang.balanced')
                : __('accounting::lang.unbalanced'),
            'pl_net' => $plNet,
        ];
    }

    /**
     * @param  Collection<int, object>  $accounts
     * @return list<array<string, mixed>>
     */
    public function buildSections(Collection $accounts, callable $roundMoney): array
    {
        $assets = $accounts->filter(fn ($a) => $this->isAsset($a))->sortBy('gl_code')->values();
        $liabilities = $accounts->filter(fn ($a) => $this->isLiability($a))->sortBy('gl_code')->values();
        $equities = $accounts->filter(fn ($a) => $this->isEquity($a) && ! $this->isPartnersCurrentLiability($a))->sortBy('gl_code')->values();

        $currentAssets = $assets->filter(fn ($a) => $this->isCurrentAsset($a))->values();
        $nonCurrentAssets = $assets->reject(fn ($a) => $this->isCurrentAsset($a))->values();
        $currentLiab = $liabilities->filter(fn ($a) => $this->isCurrentLiability($a))->values();
        $nonCurrentLiab = $liabilities->reject(fn ($a) => $this->isCurrentLiability($a))->values();

        $sum = fn (Collection $rows) => $roundMoney($rows->sum('balance'));

        $accountsGroup = function (Collection $rows, string $labelKey = '') use ($sum) {
            if ($rows->isEmpty()) {
                return [];
            }

            return [[
                'type' => 'accounts',
                'label' => $labelKey !== '' ? __('accounting::lang.'.$labelKey) : '',
                'accounts' => $rows,
                'total' => $sum($rows),
                'hide_header' => $labelKey === '',
            ]];
        };

        $totalCurrentAssets = $sum($currentAssets);
        $totalNonCurrentAssets = $sum($nonCurrentAssets);
        $totalCurrentLiab = $sum($currentLiab);
        $totalNonCurrentLiab = $sum($nonCurrentLiab);
        $totalEquity = $sum($equities);

        return [
            [
                'key' => 'assets',
                'title' => __('accounting::lang.assets'),
                'groups' => array_merge(
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_current_assets')]],
                    $accountsGroup($currentAssets),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_current_assets'), 'amount' => $totalCurrentAssets]],
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_non_current_assets')]],
                    $accountsGroup($nonCurrentAssets),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_non_current_assets'), 'amount' => $totalNonCurrentAssets]],
                    [['type' => 'grand', 'label' => __('accounting::lang.total_assets'), 'amount' => $roundMoney($totalCurrentAssets + $totalNonCurrentAssets)]],
                ),
                'total' => $roundMoney($totalCurrentAssets + $totalNonCurrentAssets),
            ],
            [
                'key' => 'liabilities',
                'title' => __('accounting::lang.liabilities'),
                'groups' => array_merge(
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_current_liabilities')]],
                    $accountsGroup($currentLiab),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_current_liabilities'), 'amount' => $totalCurrentLiab]],
                    [['type' => 'subsection', 'label' => __('accounting::lang.bs_non_current_liabilities')]],
                    $accountsGroup($nonCurrentLiab),
                    [['type' => 'subtotal', 'label' => __('accounting::lang.bs_total_non_current_liabilities'), 'amount' => $totalNonCurrentLiab]],
                    [['type' => 'grand', 'label' => __('accounting::lang.bs_total_liabilities'), 'amount' => $roundMoney($totalCurrentLiab + $totalNonCurrentLiab)]],
                ),
                'total' => $roundMoney($totalCurrentLiab + $totalNonCurrentLiab),
            ],
            [
                'key' => 'equity',
                'title' => __('accounting::lang.equity'),
                'groups' => array_merge(
                    [['type' => 'subsection', 'label' => __('accounting::lang.equity')]],
                    $accountsGroup($equities),
                    [['type' => 'grand', 'label' => __('accounting::lang.bs_total_equity'), 'amount' => $totalEquity]],
                ),
                'total' => $totalEquity,
            ],
        ];
    }

    /**
     * @param  Collection<int, object>  $accounts
     * @return array<string, mixed>
     */
    private function metrics(
        Collection $accounts,
        float $totalAssets,
        float $totalLiabilities,
        float $totalEquity,
        callable $roundMoney
    ): array {
        $currentAssets = $roundMoney(
            $accounts->filter(fn ($a) => $this->isAsset($a) && $this->isCurrentAsset($a))->sum('balance')
        );
        $currentLiabilities = $roundMoney(
            $accounts->filter(fn ($a) => $this->isLiability($a) && $this->isCurrentLiability($a))->sum('balance')
        );

        $workingCapital = $roundMoney($currentAssets - $currentLiabilities);
        $currentRatio = abs($currentLiabilities) > 0.0001 ? round($currentAssets / $currentLiabilities, 2) : null;
        $debtRatio = abs($totalAssets) > 0.0001 ? round($totalLiabilities / $totalAssets, 4) : null;
        $equityRatio = abs($totalAssets) > 0.0001 ? round($totalEquity / $totalAssets, 4) : null;

        return [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'current_assets' => $currentAssets,
            'current_liabilities' => $currentLiabilities,
            'working_capital' => $workingCapital,
            'current_ratio' => $currentRatio,
            'debt_ratio' => $debtRatio,
            'equity_ratio' => $equityRatio,
            'liquidity_percent' => $currentRatio !== null ? round($currentRatio * 100, 1) : null,
            'debt_percent' => $debtRatio !== null ? round($debtRatio * 100, 1) : null,
            'equity_percent' => $equityRatio !== null ? round($equityRatio * 100, 1) : null,
        ];
    }

    /**
     * @param  Collection<int, object>  $accounts
     * @return Collection<int, object>
     */
    private function applyCurrentYearProfitPlug(Collection $accounts, float $plNet, callable $roundMoney): Collection
    {
        $profit = $accounts->first(function ($account) {
            $gl = (string) ($account->gl_code ?? '');
            if ($this->glFamily($gl, '32201')) {
                return true;
            }

            $name = mb_strtolower(trim(($account->name_ar ?? '').' '.($account->name_en ?? '')));

            return str_contains($name, 'أرباح/خسائر العام')
                || str_contains($name, 'ارباح/خسائر العام')
                || str_contains($name, 'أرباح العام')
                || str_contains($name, 'current year profit');
        });

        if (! $profit) {
            $profit = (object) [
                'id' => -32201,
                'parent_account_id' => null,
                'name_ar' => 'أرباح/خسائر العام الحالي',
                'name_en' => 'Current Year Profit / Loss',
                'account_primary_type' => 'equity',
                'account_type' => 'equity',
                'gl_code' => '32201',
                'account_sub_type_name_en' => null,
                'account_sub_type_name_ar' => null,
                'balance' => 0.0,
                'depth' => 0,
                'has_children' => false,
            ];
            $accounts->push($profit);
            $accounts = $accounts->sortBy('gl_code')->values();
        }

        $book = $roundMoney((float) ($profit->balance ?? 0));
        $profit->balance = $roundMoney($book + $plNet);
        $profit->is_current_year_profit_plug = true;

        return $accounts->values();
    }

    /**
     * @param  Collection<int, object>  $accounts
     * @return Collection<int, object>
     */
    private function enrichTree(Collection $accounts): Collection
    {
        $byId = $accounts->keyBy('id');
        $childCountByParent = $accounts->pluck('parent_account_id')->filter()->countBy();

        return $accounts->map(function ($account) use ($byId, $childCountByParent) {
            $depth = 0;
            $parentId = $account->parent_account_id;
            $guard = 0;
            while ($parentId && $byId->has($parentId) && $guard < 12) {
                $depth++;
                $parentId = $byId->get($parentId)->parent_account_id;
                $guard++;
            }

            $account->depth = $depth;
            $account->has_children = $childCountByParent->get($account->id, 0) > 0;

            return $account;
        });
    }

    /**
     * @param  Collection<int, object>  $accounts
     * @return Collection<int, object>
     */
    private function keepNonZeroTree(Collection $accounts): Collection
    {
        $byId = $accounts->keyBy('id');
        $keepIds = [];

        foreach ($accounts as $account) {
            $isPlug = ! empty($account->is_current_year_profit_plug);
            if (! $isPlug && abs((float) ($account->balance ?? 0)) <= 0.0001) {
                continue;
            }

            $id = (int) $account->id;
            $keepIds[$id] = true;
            $parentId = $account->parent_account_id;
            $guard = 0;
            while ($parentId && $byId->has($parentId) && $guard < 12) {
                $keepIds[(int) $parentId] = true;
                $parentId = $byId->get($parentId)->parent_account_id;
                $guard++;
            }
        }

        return $accounts
            ->filter(fn ($account) => isset($keepIds[(int) $account->id]))
            ->values()
            ->map(function ($account) use ($keepIds, $accounts) {
                $account->has_children = $accounts->contains(
                    fn ($child) => (int) ($child->parent_account_id ?? 0) === (int) $account->id
                        && isset($keepIds[(int) $child->id])
                );

                return $account;
            });
    }

    private function isPlAccount(object $account): bool
    {
        $primary = strtolower(trim((string) ($account->account_primary_type ?? '')));
        $type = strtolower(trim((string) ($account->account_type ?? '')));

        if (in_array($primary, ['income', 'expenses', 'expense'], true)
            || in_array($type, ['income', 'expenses', 'expense'], true)) {
            return true;
        }

        return in_array($this->leadingDigit((string) ($account->gl_code ?? '')), ['4', '5'], true);
    }

    private function isIncomeAccount(object $account): bool
    {
        $primary = strtolower(trim((string) ($account->account_primary_type ?? '')));
        $type = strtolower(trim((string) ($account->account_type ?? '')));

        if ($primary === 'income' || $type === 'income') {
            return true;
        }

        if (in_array($primary, ['expenses', 'expense'], true) || in_array($type, ['expenses', 'expense'], true)) {
            return false;
        }

        return $this->leadingDigit((string) ($account->gl_code ?? '')) === '4';
    }

    private function isAsset(object $account): bool
    {
        $primary = strtolower(trim((string) ($account->account_primary_type ?? '')));

        return $primary === 'asset' || $this->leadingDigit((string) ($account->gl_code ?? '')) === '1';
    }

    private function isLiability(object $account): bool
    {
        if ($this->isPartnersCurrentLiability($account)) {
            return true;
        }

        $primary = strtolower(trim((string) ($account->account_primary_type ?? '')));

        return in_array($primary, ['liability', 'liabilities'], true)
            || $this->leadingDigit((string) ($account->gl_code ?? '')) === '2';
    }

    private function isEquity(object $account): bool
    {
        if ($this->isPartnersCurrentLiability($account)) {
            return false;
        }

        $primary = strtolower(trim((string) ($account->account_primary_type ?? '')));

        return $primary === 'equity' || $this->leadingDigit((string) ($account->gl_code ?? '')) === '3';
    }

    /**
     * Partners current / drawings: accountant presentation under current liabilities
     * even when COA still parks them under equity (GL families 331 and 332).
     */
    public function isPartnersCurrentLiability(object $account): bool
    {
        $gl = preg_replace('/[^0-9]/', '', (string) ($account->gl_code ?? '')) ?? '';
        if ($gl !== '' && (str_starts_with($gl, '331') || str_starts_with($gl, '332'))) {
            return true;
        }

        $name = mb_strtolower(trim((string) ($account->name_ar ?? '').' '.(string) ($account->name_en ?? '')));
        $name = str_replace(['أ', 'إ', 'آ'], 'ا', $name);

        return str_contains($name, 'جاري الشركاء')
            || str_contains($name, 'حساب جاري الشركاء')
            || str_contains($name, 'مسحوبات الملاك')
            || str_contains($name, 'مسحوبات الشركاء')
            || str_contains($name, 'partners current')
            || str_contains($name, 'owners drawings')
            || str_contains($name, 'partner drawings');
    }

    private function isCurrentAsset(object $account): bool
    {
        $type = strtolower(trim((string) ($account->account_type ?? '')));
        $subtype = strtolower(trim((string) ($account->account_sub_type_name_en ?? '')));
        $gl = preg_replace('/[^0-9]/', '', (string) ($account->gl_code ?? '')) ?? '';

        return $type === 'current_assets'
            || $subtype === 'current assets'
            || str_starts_with($gl, '11');
    }

    private function isCurrentLiability(object $account): bool
    {
        if ($this->isPartnersCurrentLiability($account)) {
            return true;
        }

        $type = strtolower(trim((string) ($account->account_type ?? '')));
        $subtype = strtolower(trim((string) ($account->account_sub_type_name_en ?? '')));
        $gl = preg_replace('/[^0-9]/', '', (string) ($account->gl_code ?? '')) ?? '';

        return $type === 'current_liabilities'
            || $subtype === 'current liabilities'
            || str_starts_with($gl, '21');
    }

    private function leadingDigit(string $glCode): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $glCode) ?? '';

        return $normalized !== '' ? $normalized[0] : '';
    }

    private function glFamily(string $gl, string $root): bool
    {
        $gl = trim($gl);
        $root = trim($root);
        if ($gl === '' || $root === '') {
            return false;
        }

        return $gl === $root || str_starts_with($gl, $root);
    }
}
