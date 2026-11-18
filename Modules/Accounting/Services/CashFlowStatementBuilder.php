<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Support\CashFlowStatementBlueprint;

/**
 * Builds the IFRS-style cash flow statement tree from the blueprint + tenant COA.
 * Phase 1: structure, COA links, expandable detail levels — amounts deferred.
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
     *   linked_accounts_count: int,
     *   missing_prefixes: list<string>
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

        $rows = [];
        $linked = 0;
        $missing = [];

        foreach (CashFlowStatementBlueprint::lines() as $line) {
            $label = $locale === 'ar' ? $line['label_ar'] : $line['label_en'];
            $resolved = $this->resolvePrefixAccounts($line['gl_prefixes'], $byGl, $accounts);

            if ($line['gl_prefixes'] !== [] && $resolved->isEmpty()) {
                foreach ($line['gl_prefixes'] as $prefix) {
                    $missing[] = (string) $prefix;
                }
            }

            $primary = $resolved->first();
            if ($primary) {
                $linked++;
            }

            $rows[] = [
                'key' => $line['key'],
                'kind' => $line['kind'],
                'section' => $line['section'],
                'label' => $label,
                'label_ar' => $line['label_ar'],
                'label_en' => $line['label_en'],
                'indent' => (int) $line['indent'],
                'amount_role' => $line['amount_role'],
                'gl_prefixes' => $line['gl_prefixes'],
                'gl_codes' => $resolved->pluck('gl_code')->map(fn ($c) => (string) $c)->values()->all(),
                'account_ids' => $resolved->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'account_label' => $primary
                    ? trim(($primary->gl_code ?? '').' — '.($locale === 'ar'
                        ? ($primary->name_ar ?: $primary->name_en)
                        : ($primary->name_en ?: $primary->name_ar)))
                    : null,
                'amount' => null,
                'is_detail' => false,
                'parent_key' => null,
            ];

            if (
                $line['kind'] === 'line'
                && $detailLevel > CashFlowStatementBlueprint::DEFAULT_DETAIL_LEVEL
                && $resolved->isNotEmpty()
            ) {
                foreach ($this->expandDetails($resolved, $accounts, $detailLevel) as $child) {
                    $childLabel = $locale === 'ar'
                        ? ($child->name_ar ?: $child->name_en)
                        : ($child->name_en ?: $child->name_ar);
                    $rows[] = [
                        'key' => $line['key'].'::'.$child->gl_code,
                        'kind' => 'line',
                        'section' => $line['section'],
                        'label' => $childLabel,
                        'label_ar' => $child->name_ar,
                        'label_en' => $child->name_en,
                        'indent' => (int) $line['indent'] + 1,
                        'amount_role' => $line['amount_role'],
                        'gl_prefixes' => [(string) $child->gl_code],
                        'gl_codes' => [(string) $child->gl_code],
                        'account_ids' => [(int) $child->id],
                        'account_label' => trim(($child->gl_code ?? '').' — '.$childLabel),
                        'amount' => null,
                        'is_detail' => true,
                        'parent_key' => $line['key'],
                    ];
                    $linked++;
                }
            }
        }

        return [
            'phase' => 'structure',
            'detail_level' => $detailLevel,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'cost_center_ids' => array_values(array_filter($costCenterIds)),
            'rows' => $rows,
            'linked_accounts_count' => $linked,
            'missing_prefixes' => array_values(array_unique($missing)),
        ];
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
            // Exact leaf codes (e.g. 32201, 32301) may exist without a group parent.
            $starts = $all->filter(function ($a) use ($prefix) {
                $gl = (string) $a->gl_code;

                return $gl === $prefix || str_starts_with($gl, $prefix);
            })->sortBy(fn ($a) => strlen((string) $a->gl_code))->values();

            if ($starts->isNotEmpty()) {
                // Prefer the shortest GL that matches (closest group root).
                $rootLen = strlen((string) $starts->first()->gl_code);
                $found = $found->merge(
                    $starts->filter(fn ($a) => strlen((string) $a->gl_code) === $rootLen)
                );
            }
        }

        return $found->unique('id')->values();
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

                // Fallback when coa_level missing: allow codes longer than roots.
                return strlen($gl) <= strlen($rootGls[0] ?? '') + ($detailLevel - 3) * 2;
            })
            ->sortBy('gl_code')
            ->values();
    }
}
