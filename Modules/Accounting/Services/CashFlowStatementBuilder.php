<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Support\CashFlowFormula;
use Modules\Accounting\Support\CashFlowStatementBlueprint;

/**
 * Builds the IFRS-style cash flow statement from named COA lines + per-line formulas.
 * Labels are fixed (Excel / level-3 style); amounts use the same equation for any date range.
 */
final class CashFlowStatementBuilder
{
    /**
     * @param  list<int|string>  $costCenterIds
     * @return array{
     *   phase: string,
     *   detail_level: int,
     *   start_date: string,
     *   end_date: string,
     *   rows: list<array<string, mixed>>,
     *   section_totals: array<string, float>,
     *   linked_accounts_count: int,
     *   missing_prefixes: list<string>,
     *   tie_out: array<string, float|bool>
     * }
     */
    public function build(
        string $startDate,
        string $endDate,
        int $detailLevel = CashFlowStatementBlueprint::DEFAULT_DETAIL_LEVEL,
        array $costCenterIds = [],
    ): array {
        $detailLevel = max(
            CashFlowStatementBlueprint::DEFAULT_DETAIL_LEVEL,
            min(CashFlowStatementBlueprint::MAX_DETAIL_LEVEL, $detailLevel)
        );

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $accounts = $this->loadAccounts();
        $byGl = $accounts->keyBy(fn ($a) => (string) $a->gl_code);
        $byId = $accounts->keyBy(fn ($a) => (int) $a->id);
        $engine = new CashFlowAmountEngine($startDate, $endDate, array_values(array_filter($costCenterIds)));

        $rows = [];
        $linked = 0;
        $missing = [];
        $amountsByKey = [];

        // First pass: leaf/group lines (not section nets / net change — those need sums).
        foreach (CashFlowStatementBlueprint::lines() as $line) {
            $formula = CashFlowFormula::normalize($line['key'], $line['amount_role']);
            $label = $locale === 'ar' ? $line['label_ar'] : $line['label_en'];
            $resolved = $this->resolveAccounts($line, $byGl, $accounts);

            // Empty resolve is valid for optional expense add-backs / prior-period (amount = 0).
            $optionalEmpty = in_array((string) $line['key'], [
                'depreciation',
                'amortization',
                'inventory_obsolescence_provision',
                'doubtful_debts_provision',
                'prior_period_adjustments',
            ], true);
            if ($line['gl_prefixes'] !== [] && $resolved->isEmpty() && ! $optionalEmpty) {
                foreach ($line['gl_prefixes'] as $prefix) {
                    $missing[] = (string) $prefix;
                }
            }

            $primary = $resolved->first();
            if ($primary) {
                $linked++;
            }

            $accountIds = $this->collectPostableIds($resolved, $accounts);
            $amount = null;
            if (! in_array($formula, [CashFlowFormula::SECTION_NET, CashFlowFormula::NET_CHANGE, CashFlowFormula::NONE], true)) {
                $relatedDep = 0.0;
                if ($formula === CashFlowFormula::INVESTING_CAPEX) {
                    $relatedDep = (float) ($amountsByKey['depreciation'] ?? 0)
                        + (float) ($amountsByKey['amortization'] ?? 0);
                }
                if ($formula === CashFlowFormula::PL_NET) {
                    // Accountant rule: ALL period revenues & expenses (not a fixed leaf list).
                    $amount = $engine->periodAllPlNet($accounts);
                    $accountIds = $accounts
                        ->filter(fn ($a) => ! $engine->isPriorPeriodAccount($a)
                            && ($engine->isIncomeAccount($a) || $engine->isExpenseAccount($a)))
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all();
                } else {
                    $amount = $engine->compute($formula, $accountIds, $byId, $relatedDep);
                }
                $amountsByKey[$line['key']] = $amount;
            }

            $rows[] = $this->makeRow(
                $line,
                $label,
                $formula,
                $resolved,
                $accountIds,
                $primary,
                $locale,
                $amount,
                false,
                null
            );

            // Always attach browsable child account rows (UI collapses them by default).
            if ($line['kind'] === 'line' && $resolved->isNotEmpty()) {
                $browseLevel = max($detailLevel, CashFlowStatementBlueprint::DEFAULT_DETAIL_LEVEL + 1);
                $children = $line['key'] === 'net_profit_before_zakat'
                    ? $this->browsePlFamilies($accounts)
                    : $this->expandDetails($resolved, $accounts, $browseLevel);

                if ($children->isEmpty() && $resolved->count() > 1) {
                    $children = $resolved->values();
                }

                $childCount = 0;
                foreach ($children as $child) {
                    if ($childCount >= 80) {
                        break;
                    }
                    $childLabel = $locale === 'ar'
                        ? ($child->name_ar ?: $child->name_en)
                        : ($child->name_en ?: $child->name_ar);
                    $childIds = $this->collectPostableIds(collect([$child]), $accounts);
                    if ($formula === CashFlowFormula::PL_NET) {
                        $childAmount = $engine->periodPlNet($childIds, $byId);
                    } else {
                        $childAmount = $engine->compute($formula, $childIds, $byId, 0.0);
                    }
                    $rows[] = [
                        'key' => $line['key'].'::'.$child->gl_code,
                        'kind' => 'line',
                        'section' => $line['section'],
                        'label' => $childLabel,
                        'label_ar' => $child->name_ar,
                        'label_en' => $child->name_en,
                        'indent' => (int) $line['indent'] + 1,
                        'amount_role' => $line['amount_role'],
                        'formula' => $formula,
                        'formula_label' => CashFlowFormula::labelAr($formula),
                        'gl_prefixes' => [(string) $child->gl_code],
                        'gl_codes' => [(string) $child->gl_code],
                        'account_ids' => $childIds,
                        'account_names' => [trim((string) ($child->gl_code ?? '').' — '.$childLabel)],
                        'account_label' => trim(($child->gl_code ?? '').' — '.$childLabel),
                        'amount' => $childAmount,
                        'is_detail' => true,
                        'parent_key' => $line['key'],
                    ];
                    $linked++;
                    $childCount++;
                }

                // Annotate parent with child count for the collapse chevron.
                if ($childCount > 0) {
                    $lastParentIdx = count($rows) - 1 - $childCount;
                    if ($lastParentIdx >= 0 && ($rows[$lastParentIdx]['key'] ?? null) === $line['key']) {
                        $rows[$lastParentIdx]['has_children'] = true;
                        $rows[$lastParentIdx]['children_count'] = $childCount;
                    }
                }
            }
        }

        // Second pass: section nets + cash bridge derived lines.
        $sectionTotals = [
            'operating' => 0.0,
            'investing' => 0.0,
            'financing' => 0.0,
        ];
        $opsBeforeWc = 0.0;
        foreach ($rows as $row) {
            if (! empty($row['is_detail']) || ($row['amount'] ?? null) === null) {
                continue;
            }
            $key = (string) $row['key'];
            $formula = (string) ($row['formula'] ?? '');
            if (in_array($formula, [CashFlowFormula::SECTION_NET, CashFlowFormula::NET_CHANGE, CashFlowFormula::NONE], true)) {
                continue;
            }
            if (in_array($formula, [CashFlowFormula::CASH_OPEN, CashFlowFormula::CASH_CLOSE], true)) {
                continue;
            }
            $amt = (float) $row['amount'];
            if ($row['section'] === 'operating') {
                $sectionTotals['operating'] = round($sectionTotals['operating'] + $amt, 2);
                if (! str_starts_with($key, 'wc_')) {
                    $opsBeforeWc = round($opsBeforeWc + $amt, 2);
                }
            } elseif ($row['section'] === 'investing') {
                $sectionTotals['investing'] = round($sectionTotals['investing'] + $amt, 2);
            } elseif ($row['section'] === 'financing') {
                $sectionTotals['financing'] = round($sectionTotals['financing'] + $amt, 2);
            }
        }

        $netChange = round(
            $sectionTotals['operating'] + $sectionTotals['investing'] + $sectionTotals['financing'],
            2
        );

        foreach ($rows as &$row) {
            $key = (string) $row['key'];
            $formula = (string) ($row['formula'] ?? '');
            if ($key === 'cash_from_operations_subtotal') {
                $row['amount'] = $opsBeforeWc;
                $amountsByKey[$key] = $opsBeforeWc;
            } elseif ($key === 'net_operating') {
                $row['amount'] = $sectionTotals['operating'];
                $amountsByKey[$key] = $sectionTotals['operating'];
            } elseif ($key === 'net_investing') {
                $row['amount'] = $sectionTotals['investing'];
                $amountsByKey[$key] = $sectionTotals['investing'];
            } elseif ($key === 'net_financing') {
                $row['amount'] = $sectionTotals['financing'];
                $amountsByKey[$key] = $sectionTotals['financing'];
            } elseif ($key === 'net_change_cash' || $formula === CashFlowFormula::NET_CHANGE) {
                $row['amount'] = $netChange;
                $amountsByKey[$key] = $netChange;
            }
        }
        unset($row);

        $cashOpen = (float) ($amountsByKey['cash_opening'] ?? 0);
        $cashClose = (float) ($amountsByKey['cash_closing'] ?? 0);
        $impliedClose = round($cashOpen + $netChange, 2);

        return [
            'phase' => 'amounts',
            'detail_level' => $detailLevel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cost_center_ids' => array_values(array_filter($costCenterIds)),
            'rows' => $rows,
            'section_totals' => $sectionTotals,
            'linked_accounts_count' => $linked,
            'missing_prefixes' => array_values(array_unique($missing)),
            'tie_out' => [
                'cash_opening' => $cashOpen,
                'net_change' => $netChange,
                'cash_closing' => $cashClose,
                'implied_closing' => $impliedClose,
                'difference' => round($cashClose - $impliedClose, 2),
                'is_balanced' => abs($cashClose - $impliedClose) < 0.05,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     * @param  Collection<int, AccountingAccount>  $resolved
     * @param  list<int>  $accountIds
     * @return array<string, mixed>
     */
    private function makeRow(
        array $line,
        string $label,
        string $formula,
        Collection $resolved,
        array $accountIds,
        mixed $primary,
        string $locale,
        ?float $amount,
        bool $isDetail,
        ?string $parentKey,
    ): array {
        $names = $resolved->map(function ($a) use ($locale) {
            return trim((string) ($locale === 'ar'
                ? ($a->name_ar ?: $a->name_en)
                : ($a->name_en ?: $a->name_ar)));
        })->filter()->values()->all();

        return [
            'key' => $line['key'],
            'kind' => $line['kind'],
            'section' => $line['section'],
            'label' => $label,
            'label_ar' => $line['label_ar'],
            'label_en' => $line['label_en'],
            'indent' => (int) $line['indent'],
            'amount_role' => $line['amount_role'],
            'formula' => $formula,
            'formula_label' => CashFlowFormula::labelAr($formula),
            'gl_prefixes' => $line['gl_prefixes'],
            'gl_codes' => $resolved->pluck('gl_code')->map(fn ($c) => (string) $c)->values()->all(),
            'account_ids' => $accountIds,
            'account_names' => $names,
            'account_label' => $primary
                ? trim(($primary->gl_code ?? '').' — '.($locale === 'ar'
                    ? ($primary->name_ar ?: $primary->name_en)
                    : ($primary->name_en ?: $primary->name_ar)))
                : null,
            'amount' => $amount,
            'is_detail' => $isDetail,
            'parent_key' => $parentKey,
            'has_children' => false,
            'children_count' => 0,
        ];
    }

    /**
     * Top income/expense families for browsing under net profit (not every leaf).
     *
     * @param  Collection<int, AccountingAccount>  $all
     * @return Collection<int, AccountingAccount>
     */
    private function browsePlFamilies(Collection $all): Collection
    {
        return $all
            ->filter(function ($a) {
                $gl = preg_replace('/[^0-9]/', '', (string) ($a->gl_code ?? '')) ?? '';
                if ($gl === '' || strlen($gl) < 2) {
                    return false;
                }
                // Families like 41, 42, 51, 54…
                if (! in_array($gl[0], ['4', '5'], true) || strlen($gl) > 2) {
                    return false;
                }
                $type = strtolower((string) ($a->account_primary_type ?? $a->account_type ?? ''));

                return in_array($type, ['income', 'expenses', 'expense'], true)
                    || in_array($gl[0], ['4', '5'], true);
            })
            ->sortBy('gl_code')
            ->values();
    }

    /**
     * Resolve by account name first (accountant rule), then GL prefixes.
     *
     * @param  array<string, mixed>  $line
     * @param  Collection<string, AccountingAccount>  $byGl
     * @param  Collection<int, AccountingAccount>  $all
     * @return Collection<int, AccountingAccount>
     */
    private function resolveAccounts(array $line, Collection $byGl, Collection $all): Collection
    {
        $key = (string) ($line['key'] ?? '');

        // Operating add-backs: expense accounts only (مصروف), never مجمع / BS allowance.
        if ($key === 'depreciation') {
            return $this->resolveExpenseByNameRules($all, include: ['اهلاك', 'هلاك', 'depreciation'], exclude: ['اطفاء', 'اطفا', 'amort', 'مجمع', 'accumulated', 'تقديم', 'consumable']);
        }
        if ($key === 'amortization') {
            return $this->resolveExpenseByNameRules($all, include: ['اطفاء', 'اطفا', 'amortization', 'amortisation'], exclude: ['مجمع', 'accumulated']);
        }
        if ($key === 'inventory_obsolescence_provision') {
            return $this->resolveExpenseByNameRules($all, include: ['تقادم', 'هبوط المخزون', 'obsolescence', 'inventory write'], exclude: ['مجمع', 'accumulated']);
        }
        if ($key === 'doubtful_debts_provision') {
            return $this->resolveExpenseByNameRules($all, include: ['ديون معدومه', 'خسائر ائتمانيه', 'مشكوك', 'bad debt', 'credit loss', 'expected credit'], exclude: ['مجمع', 'accumulated']);
        }
        if ($key === 'prior_period_adjustments') {
            // Exact COA line by name; if absent → empty → amount 0. Not P&L.
            $byName = $all->filter(function ($a) {
                $hay = $this->normalizeName(($a->name_ar ?? '').' '.($a->name_en ?? ''));

                return str_contains($hay, 'تعديلات سنوات سابقه')
                    || str_contains($hay, 'prior period');
            })->values();
            if ($byName->isNotEmpty()) {
                return $byName;
            }

            return $this->resolvePrefixAccounts(['32401', '324'], $byGl, $all);
        }

        // Broad P&L / cash families are safer by GL tree than free-text names.
        $prefixOnly = in_array($key, [
            'net_profit_before_zakat',
            'cash_opening',
            'cash_closing',
            'net_change_cash',
            'fin_dividends',
        ], true);

        if (! $prefixOnly) {
            $needles = $this->nameNeedles($line);
            if ($needles !== []) {
                $byName = $all->filter(function ($a) use ($needles) {
                    $hay = $this->normalizeName(($a->name_ar ?? '').' '.($a->name_en ?? ''));
                    foreach ($needles as $needle) {
                        if ($needle !== '' && str_contains($hay, $needle)) {
                            return true;
                        }
                    }

                    return false;
                })->values();

                // Prefer closest group roots (shortest GL) among name matches.
                if ($byName->isNotEmpty()) {
                    $minLen = $byName->min(fn ($a) => strlen((string) $a->gl_code));
                    $roots = $byName->filter(fn ($a) => strlen((string) $a->gl_code) === $minLen)->values();
                    if ($roots->isNotEmpty()) {
                        return $roots;
                    }
                }
            }
        }

        return $this->resolvePrefixAccounts($line['gl_prefixes'] ?? [], $byGl, $all);
    }

    /**
     * Match expense accounts by Arabic/English name rules (period charges only).
     *
     * @param  Collection<int, AccountingAccount>  $all
     * @param  list<string>  $include
     * @param  list<string>  $exclude
     * @return Collection<int, AccountingAccount>
     */
    private function resolveExpenseByNameRules(Collection $all, array $include, array $exclude = []): Collection
    {
        $include = array_map(fn ($n) => $this->normalizeName($n), $include);
        $exclude = array_map(fn ($n) => $this->normalizeName($n), $exclude);

        return $all->filter(function ($a) use ($include, $exclude) {
            $type = strtolower((string) ($a->account_primary_type ?? $a->account_type ?? ''));
            $gl = preg_replace('/[^0-9]/', '', (string) ($a->gl_code ?? '')) ?? '';
            $isExpense = in_array($type, ['expenses', 'expense'], true) || ($gl !== '' && str_starts_with($gl, '5'));
            if (! $isExpense) {
                return false;
            }

            $hay = $this->normalizeName(($a->name_ar ?? '').' '.($a->name_en ?? ''));
            foreach ($exclude as $ex) {
                if ($ex !== '' && str_contains($hay, $ex)) {
                    return false;
                }
            }
            foreach ($include as $inc) {
                if ($inc !== '' && str_contains($hay, $inc)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    /**
     * @param  array<string, mixed>  $line
     * @return list<string>
     */
    private function nameNeedles(array $line): array
    {
        $raw = array_filter([
            $line['label_ar'] ?? null,
            $line['label_en'] ?? null,
        ]);

        // Strip formula-ish suffixes from labels for matching.
        $cleaned = [];
        foreach ($raw as $label) {
            $label = (string) $label;
            $label = preg_replace('/\s*[—\-].*$/u', '', $label) ?? $label;
            $label = str_replace(['- مكون', '— charged', '— current', '— non-current'], '', $label);
            $n = $this->normalizeName($label);
            if (mb_strlen($n) >= 3) {
                $cleaned[] = $n;
            }
        }

        // Extra aliases for common COA wording differences.
        $key = (string) ($line['key'] ?? '');
        $aliases = match ($key) {
            'wc_inventory' => ['المخزون', 'inventory'],
            'wc_trade_receivables' => ['ذمم تجارية مدينة', 'trade receivable'],
            'wc_trade_payables' => ['الموردون', 'دائنون', 'trade payable', 'suppliers'],
            'net_profit_before_zakat' => [], // all P&L — handled separately
            'cash_opening', 'cash_closing', 'net_change_cash' => ['نقد', 'بنوك', 'cash', 'bank'],
            default => [],
        };

        foreach ($aliases as $alias) {
            $cleaned[] = $this->normalizeName($alias);
        }

        return array_values(array_unique(array_filter($cleaned)));
    }

    private function normalizeName(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $value);

        return $value;
    }

    /**
     * @return Collection<int, AccountingAccount>
     */
    private function loadAccounts(): Collection
    {
        $cols = [
            'id',
            'gl_code',
            'name_ar',
            'name_en',
            'parent_account_id',
            'account_primary_type',
            'account_type',
            'allow_direct_posting',
        ];
        if (Schema::hasColumn('accounting_accounts', 'coa_level')) {
            $cols[] = 'coa_level';
        }

        return AccountingAccount::query()->orderBy('gl_code')->get($cols);
    }

    /**
     * @param  list<string>  $prefixes
     * @param  Collection<string, AccountingAccount>  $byGl
     * @param  Collection<int, AccountingAccount>  $all
     * @return Collection<int, AccountingAccount>
     */
    private function resolvePrefixAccounts(array $prefixes, Collection $byGl, Collection $all): Collection
    {
        $found = collect();
        foreach ($prefixes as $prefix) {
            $prefix = (string) $prefix;
            if ($prefix === '') {
                continue;
            }
            if ($byGl->has($prefix)) {
                $found->push($byGl->get($prefix));
                continue;
            }
            $starts = $all->filter(function ($a) use ($prefix) {
                $gl = (string) $a->gl_code;

                return $gl === $prefix || str_starts_with($gl, $prefix);
            })->sortBy(fn ($a) => strlen((string) $a->gl_code))->values();

            if ($starts->isNotEmpty()) {
                $rootLen = strlen((string) $starts->first()->gl_code);
                $found = $found->merge(
                    $starts->filter(fn ($a) => strlen((string) $a->gl_code) === $rootLen)
                );
            }
        }

        return $found->unique('id')->values();
    }

    /**
     * Include the resolved nodes plus all descendants that can hold balances.
     *
     * @param  Collection<int, AccountingAccount>  $roots
     * @param  Collection<int, AccountingAccount>  $all
     * @return list<int>
     */
    private function collectPostableIds(Collection $roots, Collection $all): array
    {
        if ($roots->isEmpty()) {
            return [];
        }

        $rootGls = $roots->pluck('gl_code')->map(fn ($c) => (string) $c)->all();
        $ids = $all->filter(function ($a) use ($rootGls) {
            $gl = (string) $a->gl_code;
            foreach ($rootGls as $rootGl) {
                if ($gl === $rootGl || str_starts_with($gl, $rootGl)) {
                    return true;
                }
            }

            return false;
        })->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        return $ids;
    }

    /**
     * @param  Collection<int, AccountingAccount>  $roots
     * @param  Collection<int, AccountingAccount>  $all
     * @return Collection<int, AccountingAccount>
     */
    private function expandDetails(Collection $roots, Collection $all, int $detailLevel): Collection
    {
        $rootIds = $roots->pluck('id')->all();
        $rootGls = $roots->pluck('gl_code')->map(fn ($c) => (string) $c)->all();

        return $all
            ->filter(function ($a) use ($rootIds, $rootGls, $detailLevel) {
                if (in_array((int) $a->id, $rootIds, true)) {
                    return false;
                }
                $gl = (string) $a->gl_code;
                $under = false;
                foreach ($rootGls as $rootGl) {
                    if ($gl !== $rootGl && str_starts_with($gl, $rootGl)) {
                        $under = true;
                        break;
                    }
                }
                if (! $under) {
                    return false;
                }
                $level = (int) ($a->coa_level ?? 0);
                if ($level > 0) {
                    return $level <= $detailLevel;
                }

                return strlen($gl) <= strlen($rootGls[0] ?? '') + ($detailLevel - 3) * 2;
            })
            ->sortBy('gl_code')
            ->values();
    }
}
