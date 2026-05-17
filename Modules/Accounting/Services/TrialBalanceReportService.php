<?php

declare(strict_types=1);

namespace Modules\Accounting\Services;

use App\Helpers\CurrencyHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

final class TrialBalanceReportService
{
  public const PRIMARY_TYPE_ORDER = [
        'asset',
        'liability',
        'liabilities',
        'equity',
        'income',
        'expenses',
        'expense',
    ];

    /** @var array<string, string> */
    public const CHART_COLORS = [
        '#1B84FF',
        '#17C653',
        '#F6C000',
        '#7239EA',
        '#F8285A',
        '#50CD89',
    ];

    /** @return array{closing_debit_balance: float, closing_credit_balance: float} */
    public static function closingBalance(object $account): array
    {
        $debitSide = round((float) ($account->debit_opening_balance ?? 0) + (float) ($account->debit_balance ?? 0), 2);
        $creditSide = round((float) ($account->credit_opening_balance ?? 0) + (float) ($account->credit_balance ?? 0), 2);
        $closing = round($creditSide - $debitSide, 2);

        return [
            'closing_debit_balance' => $closing < 0 ? round(abs($closing), 2) : 0.0,
            'closing_credit_balance' => $closing >= 0 ? $closing : 0.0,
        ];
    }

    public static function balanceTypeLabel(float $debit, float $credit): string
    {
        $diff = round($debit - $credit, 2);
        if (abs($diff) < 0.005) {
            return __('accounting::lang.tb_balance_type_zero');
        }

        return $diff > 0
            ? __('accounting::lang.tb_balance_type_debit')
            : __('accounting::lang.tb_balance_type_credit');
    }

    public static function signedClosing(object $account): float
    {
        $c = static::closingBalance($account);

        return round((float) $c['closing_credit_balance'] - (float) $c['closing_debit_balance'], 2);
    }

    /** @return array<string, mixed> */
    public static function buildAnalytics(Collection $rows, bool $aggregated = false): array
    {
        $totalDebitOpening = 0.0;
        $totalCreditOpening = 0.0;
        $totalDebitPeriod = 0.0;
        $totalCreditPeriod = 0.0;
        $totalClosingDebit = 0.0;
        $totalClosingCredit = 0.0;
        $activeCount = 0;
        $movementByAccount = [];

        $typeTotals = [];

        foreach ($rows as $account) {
            $dOpen = (float) ($account->debit_opening_balance ?? 0);
            $cOpen = (float) ($account->credit_opening_balance ?? 0);
            $dPeriod = (float) ($account->debit_balance ?? 0);
            $cPeriod = (float) ($account->credit_balance ?? 0);
            $closing = static::closingBalance($account);

            $totalDebitOpening += $dOpen;
            $totalCreditOpening += $cOpen;
            $totalDebitPeriod += $dPeriod;
            $totalCreditPeriod += $cPeriod;
            $totalClosingDebit += (float) $closing['closing_debit_balance'];
            $totalClosingCredit += (float) $closing['closing_credit_balance'];

            $movement = $dPeriod + $cPeriod + $dOpen + $cOpen;
            if ($movement > 0.0001) {
                $activeCount++;
            }

            $movementByAccount[] = [
                'gl_code' => $account->gl_code ?? '',
                'name' => $account->name ?? '',
                'movement' => round($dPeriod + $cPeriod, 2),
                'closing_signed' => static::signedClosing($account),
            ];

            $typeKey = static::normalizePrimaryType((string) ($account->account_primary_type ?? 'other'));
            if (! isset($typeTotals[$typeKey])) {
                $typeTotals[$typeKey] = 0.0;
            }
            $typeTotals[$typeKey] += abs(static::signedClosing($account));
        }

        usort($movementByAccount, fn ($a, $b) => $b['movement'] <=> $a['movement']);
        $topMovement = array_slice($movementByAccount, 0, 5);

        $difference = abs($totalClosingDebit - $totalClosingCredit);
        $isBalanced = $difference < 0.005;

        $chart = static::buildChartPayload($typeTotals);

        return [
            'kpis' => [
                'total_debit_period' => round($totalDebitPeriod, 2),
                'total_credit_period' => round($totalCreditPeriod, 2),
                'total_debit_opening' => round($totalDebitOpening, 2),
                'total_credit_opening' => round($totalCreditOpening, 2),
                'closing_debit' => round($totalClosingDebit, 2),
                'closing_credit' => round($totalClosingCredit, 2),
                'difference' => round($difference, 2),
                'is_balanced' => $isBalanced,
                'account_count' => $rows->count(),
                'active_accounts' => $activeCount,
                'inactive_accounts' => max(0, $rows->count() - $activeCount),
                'aggregated' => $aggregated,
            ],
            'chart' => $chart,
            'top_movement' => $topMovement,
            'type_totals' => $typeTotals,
        ];
    }

    /** @param  array<string, float>  $typeTotals */
    public static function buildChartPayload(array $typeTotals): array
    {
        $labels = [];
        $series = [];
        $colors = [];
        $i = 0;

        foreach (self::PRIMARY_TYPE_ORDER as $type) {
            if (! isset($typeTotals[$type]) || $typeTotals[$type] < 0.0001) {
                continue;
            }
            $labels[] = Lang::has('accounting::lang.'.$type)
                ? __('accounting::lang.'.$type)
                : ucfirst($type);
            $series[] = round($typeTotals[$type], 2);
            $colors[] = self::CHART_COLORS[$i % count(self::CHART_COLORS)];
            $i++;
        }

        foreach ($typeTotals as $type => $amount) {
            if (in_array($type, self::PRIMARY_TYPE_ORDER, true) || $amount < 0.0001) {
                continue;
            }
            $labels[] = $type;
            $series[] = round($amount, 2);
            $colors[] = self::CHART_COLORS[$i % count(self::CHART_COLORS)];
            $i++;
        }

        return compact('labels', 'series', 'colors');
    }

    public static function normalizePrimaryType(string $type): string
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'liability' => 'liabilities',
            'expense' => 'expenses',
            default => $type !== '' ? $type : 'other',
        };
    }

    public static function growthPercent(?float $current, ?float $previous): ?float
    {
        return CurrencyHelper::growth_percent($current, $previous);
    }
}
