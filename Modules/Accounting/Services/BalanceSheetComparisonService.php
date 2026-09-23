<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

/**
 * Multi-period comparative balance sheet (as-at each period end_date).
 * Period resolution reuses IncomeStatementComparisonService request shape.
 */
final class BalanceSheetComparisonService
{
    /**
     * @param  array<int, array{key: string, label: string, start_date: string, end_date: string}>  $periods
     * @param  callable(string, string): array  $buildDataset  (start_date, end_date) → BS dataset
     * @return array{periods: array<int, array{key: string, label: string, start_date: string, end_date: string}>, rows: array<int, array<string, mixed>>}
     */
    public static function buildComparisonTable(array $periods, callable $buildDataset): array
    {
        $datasets = [];
        foreach ($periods as $period) {
            $datasets[$period['key']] = $buildDataset($period['start_date'], $period['end_date']);
        }

        $firstKey = $periods[0]['key'] ?? null;
        $blueprint = $firstKey !== null ? ($datasets[$firstKey]['sections'] ?? []) : [];
        $rows = [];

        foreach ($blueprint as $sectionIndex => $section) {
            $rows[] = [
                'type' => 'section',
                'label' => (string) ($section['title'] ?? ''),
                'amounts' => [],
            ];

            foreach ($section['groups'] ?? [] as $groupIndex => $group) {
                $groupType = (string) ($group['type'] ?? '');

                if ($groupType === 'subsection') {
                    $rows[] = [
                        'type' => 'subsection',
                        'label' => (string) ($group['label'] ?? ''),
                        'amounts' => [],
                    ];

                    continue;
                }

                if ($groupType === 'accounts') {
                    foreach (static::mergeAccountRows($datasets, $periods, $sectionIndex, $groupIndex) as $accountRow) {
                        $rows[] = $accountRow;
                    }

                    continue;
                }

                if (in_array($groupType, ['subtotal', 'grand'], true)) {
                    $amounts = [];
                    foreach ($periods as $period) {
                        $peer = $datasets[$period['key']]['sections'][$sectionIndex]['groups'][$groupIndex] ?? null;
                        $amounts[$period['key']] = (float) ($peer['amount'] ?? 0);
                    }

                    $rows[] = [
                        'type' => 'summary',
                        'label' => (string) ($group['label'] ?? ''),
                        'row_class' => $groupType === 'grand' ? 'is-grand' : 'is-subtotal',
                        'amounts' => $amounts,
                    ];
                }
            }
        }

        $equationAmounts = [];
        foreach ($periods as $period) {
            $equationAmounts[$period['key']] = (float) ($datasets[$period['key']]['total_liab_owners'] ?? 0);
        }

        $rows[] = [
            'type' => 'summary',
            'label' => __('accounting::lang.bs_total_liab_equity'),
            'row_class' => 'is-grand',
            'amounts' => $equationAmounts,
        ];

        return [
            'periods' => $periods,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, array>  $datasets
     * @param  array<int, array{key: string, label: string, start_date: string, end_date: string}>  $periods
     * @return array<int, array<string, mixed>>
     */
    private static function mergeAccountRows(
        array $datasets,
        array $periods,
        int $sectionIndex,
        int $groupIndex
    ): array {
        $byId = [];

        foreach ($periods as $period) {
            $periodKey = $period['key'];
            $group = $datasets[$periodKey]['sections'][$sectionIndex]['groups'][$groupIndex] ?? null;
            $accounts = collect($group['accounts'] ?? []);

            foreach ($accounts as $account) {
                $id = (int) ($account->id ?? 0);
                if ($id === 0) {
                    continue;
                }

                if (! isset($byId[$id])) {
                    $byId[$id] = [
                        'type' => 'account',
                        'account_id' => $id,
                        'parent_account_id' => $account->parent_account_id ?? null,
                        'gl_code' => $account->gl_code ?? '',
                        'name_ar' => $account->name_ar ?? '',
                        'name_en' => $account->name_en ?? '',
                        'depth' => (int) ($account->depth ?? 0),
                        'has_children' => (bool) ($account->has_children ?? false),
                        'amounts' => [],
                    ];
                }

                $byId[$id]['amounts'][$periodKey] = (float) ($account->balance ?? 0);
            }
        }

        return collect($byId)
            ->sortBy(fn ($row) => (string) ($row['gl_code'] ?? ''))
            ->values()
            ->all();
    }
}
