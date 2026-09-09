<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class IncomeStatementComparisonService
{
    public const MAX_PERIODS = 4;

    public const MIN_PERIODS = 2;

    /**
     * @return array<int, array{key: string, label: string, start_date: string, end_date: string}>
     */
    public static function resolvePeriodsFromRequest(Request $request, string $defaultStart, string $defaultEnd): array
    {
        if (! $request->boolean('comparison_enabled')) {
            return [];
        }

        $raw = $request->input('periods', []);
        if (! is_array($raw)) {
            $raw = [];
        }

        $periods = [];
        foreach ($raw as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $start = trim((string) ($row['start_date'] ?? ''));
            $end = trim((string) ($row['end_date'] ?? ''));
            if ($start === '' || $end === '') {
                continue;
            }

            if (Carbon::parse($start)->gt(Carbon::parse($end))) {
                continue;
            }

            $periods[] = [
                'key' => 'period_'.$index,
                'label' => trim((string) ($row['label'] ?? '')) ?: static::defaultPeriodLabel((int) $index),
                'start_date' => $start,
                'end_date' => $end,
            ];

            if (count($periods) >= self::MAX_PERIODS) {
                break;
            }
        }

        if ($periods === [] && $defaultStart !== '' && $defaultEnd !== '') {
            $periods[] = [
                'key' => 'period_0',
                'label' => static::defaultPeriodLabel(0),
                'start_date' => $defaultStart,
                'end_date' => $defaultEnd,
            ];
        }

        return count($periods) >= self::MIN_PERIODS ? array_values($periods) : [];
    }

    /**
     * @param  array<int, array{key: string, label: string, start_date: string, end_date: string}>  $periods
     * @param  callable(string, string): array  $buildDataset
     * @return array{periods: array<int, array{key: string, label: string, start_date: string, end_date: string}>, rows: array<int, array<string, mixed>>}
     */
    public static function buildComparisonTable(array $periods, callable $buildDataset): array
    {
        $datasets = [];
        foreach ($periods as $period) {
            $datasets[$period['key']] = $buildDataset($period['start_date'], $period['end_date']);
        }

        $rows = [];
        foreach (static::sectionBlueprint() as $section) {
            if (! empty($section['visible']) && ! static::sectionVisible($datasets, $section['visible'])) {
                continue;
            }

            if (($section['type'] ?? '') === 'section') {
                $rows[] = [
                    'type' => 'section',
                    'label' => __($section['label_key']),
                    'amounts' => [],
                ];

                continue;
            }

            if (($section['type'] ?? '') === 'accounts') {
                $accountRows = static::mergeAccountRows($datasets, (string) $section['collection']);
                foreach ($accountRows as $accountRow) {
                    $rows[] = $accountRow;
                }

                continue;
            }

            if (($section['type'] ?? '') === 'summary') {
                if (! empty($section['visible']) && ! static::sectionVisible($datasets, $section['visible'])) {
                    continue;
                }

                $amounts = [];
                foreach ($periods as $period) {
                    $data = $datasets[$period['key']]['data'] ?? [];
                    $value = (float) ($data[$section['data_key']] ?? 0);
                    if (! empty($section['negate'])) {
                        $value *= -1;
                    }
                    $amounts[$period['key']] = $value;
                }

                $rows[] = [
                    'type' => 'summary',
                    'label' => is_callable($section['label'] ?? null)
                        ? ($section['label'])($datasets)
                        : (string) ($section['label'] ?? ''),
                    'row_class' => (string) ($section['row_class'] ?? 'is-subtotal'),
                    'data_key' => (string) ($section['data_key'] ?? ''),
                    'amounts' => $amounts,
                ];
            }
        }

        return [
            'periods' => $periods,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<string, array>  $datasets
     * @return array<int, array<string, mixed>>
     */
    private static function mergeAccountRows(array $datasets, string $collectionKey): array
    {
        $byId = [];

        foreach ($datasets as $periodKey => $dataset) {
            $accounts = collect($dataset[$collectionKey] ?? []);
            foreach ($accounts as $account) {
                $id = (int) ($account->id ?? 0);
                if ($id <= 0) {
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

                $byId[$id]['amounts'][$periodKey] = (float) ($account->amount ?? 0);
            }
        }

        return collect($byId)
            ->sortBy(fn ($row) => (string) ($row['gl_code'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array>  $datasets
     * @param  array<string, mixed>  $rule
     */
    private static function sectionVisible(array $datasets, array $rule): bool
    {
        foreach ($datasets as $dataset) {
            $collection = collect($dataset[$rule['collection'] ?? ''] ?? []);
            if ($collection->isNotEmpty()) {
                return true;
            }

            $data = $dataset['data'] ?? [];
            $amount = abs((float) ($data[$rule['data_key'] ?? ''] ?? 0));

            if ($amount > 0.0001) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function sectionBlueprint(): array
    {
        return [
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_gross_revenue'],
            ['type' => 'accounts', 'collection' => 'grossRevenueAccounts'],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.total').' '.__('accounting::lang.income_statement_gross_revenue'),
                'data_key' => 'gross_revenue',
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_sales_returns', 'visible' => ['collection' => 'salesReturnAccounts', 'data_key' => 'sales_returns']],
            ['type' => 'accounts', 'collection' => 'salesReturnAccounts', 'visible' => ['collection' => 'salesReturnAccounts', 'data_key' => 'sales_returns']],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.total').' '.__('accounting::lang.income_statement_sales_returns'),
                'data_key' => 'sales_returns',
                'negate' => true,
                'visible' => ['collection' => 'salesReturnAccounts', 'data_key' => 'sales_returns'],
            ],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_net_sales'),
                'data_key' => 'net_sales',
                'row_class' => 'is-grand',
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_cost_of_revenue'],
            ['type' => 'accounts', 'collection' => 'cogsAccounts'],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_total_cost_of_revenue'),
                'data_key' => 'cost_of_revenue',
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_operating_expenses', 'visible' => ['collection' => 'operatingExpenseAccounts', 'data_key' => 'total_operating_expense']],
            ['type' => 'accounts', 'collection' => 'operatingExpenseAccounts', 'visible' => ['collection' => 'operatingExpenseAccounts', 'data_key' => 'total_operating_expense']],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_total_operating_expenses'),
                'data_key' => 'total_operating_expense',
                'visible' => ['collection' => 'operatingExpenseAccounts', 'data_key' => 'total_operating_expense'],
            ],
            [
                'type' => 'summary',
                'label' => __('report::general.gross_profit'),
                'data_key' => 'gross_profit',
                'row_class' => 'is-profit-row',
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_selling_expenses', 'visible' => ['collection' => 'sellingExpenseAccounts', 'data_key' => 'total_selling_expense']],
            ['type' => 'accounts', 'collection' => 'sellingExpenseAccounts', 'visible' => ['collection' => 'sellingExpenseAccounts', 'data_key' => 'total_selling_expense']],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_total_selling_expenses'),
                'data_key' => 'total_selling_expense',
                'visible' => ['collection' => 'sellingExpenseAccounts', 'data_key' => 'total_selling_expense'],
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_administrative_expenses', 'visible' => ['collection' => 'administrativeExpenseAccounts', 'data_key' => 'total_administrative_expense']],
            ['type' => 'accounts', 'collection' => 'administrativeExpenseAccounts', 'visible' => ['collection' => 'administrativeExpenseAccounts', 'data_key' => 'total_administrative_expense']],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_total_administrative_expenses'),
                'data_key' => 'total_administrative_expense',
                'visible' => ['collection' => 'administrativeExpenseAccounts', 'data_key' => 'total_administrative_expense'],
            ],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_operating_profit'),
                'data_key' => 'operating_profit',
                'row_class' => 'is-grand',
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_other_income', 'visible' => ['collection' => 'otherIncomeAccounts', 'data_key' => 'total_other_income']],
            ['type' => 'accounts', 'collection' => 'otherIncomeAccounts', 'visible' => ['collection' => 'otherIncomeAccounts', 'data_key' => 'total_other_income']],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_total_other_income'),
                'data_key' => 'total_other_income',
                'visible' => ['collection' => 'otherIncomeAccounts', 'data_key' => 'total_other_income'],
            ],
            ['type' => 'section', 'label_key' => 'accounting::lang.income_statement_other_expenses', 'visible' => ['collection' => 'otherExpenseAccounts', 'data_key' => 'total_other_expense']],
            ['type' => 'accounts', 'collection' => 'otherExpenseAccounts', 'visible' => ['collection' => 'otherExpenseAccounts', 'data_key' => 'total_other_expense']],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_statement_total_other_expenses'),
                'data_key' => 'total_other_expense',
                'visible' => ['collection' => 'otherExpenseAccounts', 'data_key' => 'total_other_expense'],
            ],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.income_before_tax'),
                'data_key' => 'income_before_tax',
            ],
            [
                'type' => 'summary',
                'label' => static fn (array $datasets) => __('accounting::lang.tax_amount').' ('.number_format((float) (($datasets[array_key_first($datasets)]['data']['tax_percent'] ?? 0)), 0).'%)',
                'data_key' => 'tax_amount',
                'negate' => true,
            ],
            [
                'type' => 'summary',
                'label' => __('accounting::lang.net_profit'),
                'data_key' => 'net_profit',
                'row_class' => 'is-profit-row',
            ],
        ];
    }

    private static function defaultPeriodLabel(int $index): string
    {
        $labels = [
            __('accounting::lang.is_compare_period_first'),
            __('accounting::lang.is_compare_period_second'),
            __('accounting::lang.is_compare_period_third'),
            __('accounting::lang.is_compare_period_fourth'),
        ];

        return $labels[$index] ?? __('accounting::lang.is_compare_period_n', ['n' => $index + 1]);
    }
}
