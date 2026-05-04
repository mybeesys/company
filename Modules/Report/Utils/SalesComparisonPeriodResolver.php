<?php

namespace Modules\Report\Utils;

use Carbon\Carbon;

class SalesComparisonPeriodResolver
{
    public const PRESET_CUSTOM = 'custom';

    /** @var list<string> */
    public const PRESETS = [
        self::PRESET_CUSTOM,
        'today',
        'yesterday',
        'this_week',
        'last_week',
        'month_to_date',
        'this_month',
        'last_month',
        'this_quarter',
        'last_quarter',
        'year_to_date',
        'this_year',
        'last_year',
        'last_7_days',
        'last_30_days',
        'last_90_days',
    ];

    /**
     * @return array{0: string, 1: string}|null [Y-m-d from, Y-m-d to]
     */
    public static function resolve(?string $preset, ?string $customRange): ?array
    {
        $preset = $preset ?: self::PRESET_CUSTOM;
        if (! in_array($preset, self::PRESETS, true)) {
            $preset = self::PRESET_CUSTOM;
        }

        if ($preset === self::PRESET_CUSTOM) {
            return self::parseCustomRange($customRange);
        }

        return self::presetToRange($preset);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function parseCustomRange(?string $input): ?array
    {
        if ($input === null || trim($input) === '') {
            return null;
        }
        $parts = explode(' - ', $input);
        if (count($parts) !== 2) {
            return null;
        }
        $from = date('Y-m-d', strtotime(trim($parts[0])));
        $to = date('Y-m-d', strtotime(trim($parts[1])));
        if (! $from || ! $to || strtotime($from) === false || strtotime($to) === false) {
            return null;
        }
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function presetToRange(string $preset): ?array
    {
        $now = Carbon::now();

        switch ($preset) {
            case 'today':
                return [
                    $now->copy()->startOfDay()->format('Y-m-d'),
                    $now->copy()->endOfDay()->format('Y-m-d'),
                ];

            case 'yesterday':
                $d = $now->copy()->subDay();

                return [$d->format('Y-m-d'), $d->format('Y-m-d')];

            case 'this_week':
                return [
                    $now->copy()->startOfWeek()->format('Y-m-d'),
                    $now->copy()->endOfWeek()->format('Y-m-d'),
                ];

            case 'last_week':
                $start = $now->copy()->subWeek()->startOfWeek();
                $end = $now->copy()->subWeek()->endOfWeek();

                return [$start->format('Y-m-d'), $end->format('Y-m-d')];

            case 'this_month':
                return [
                    $now->copy()->startOfMonth()->format('Y-m-d'),
                    $now->copy()->endOfMonth()->format('Y-m-d'),
                ];

            case 'last_month':
                $ref = $now->copy()->subMonthNoOverflow();

                return [
                    $ref->copy()->startOfMonth()->format('Y-m-d'),
                    $ref->copy()->endOfMonth()->format('Y-m-d'),
                ];

            case 'month_to_date':
                return [
                    $now->copy()->startOfMonth()->format('Y-m-d'),
                    $now->copy()->format('Y-m-d'),
                ];

            case 'this_quarter':
                return [
                    $now->copy()->startOfQuarter()->format('Y-m-d'),
                    $now->copy()->endOfQuarter()->format('Y-m-d'),
                ];

            case 'last_quarter':
                $endLast = $now->copy()->startOfQuarter()->subDay();

                return [
                    $endLast->copy()->startOfQuarter()->format('Y-m-d'),
                    $endLast->copy()->endOfQuarter()->format('Y-m-d'),
                ];

            case 'this_year':
                return [
                    $now->copy()->startOfYear()->format('Y-m-d'),
                    $now->copy()->endOfYear()->format('Y-m-d'),
                ];

            case 'last_year':
                $y = $now->copy()->subYear();

                return [
                    $y->copy()->startOfYear()->format('Y-m-d'),
                    $y->copy()->endOfYear()->format('Y-m-d'),
                ];

            case 'year_to_date':
                return [
                    $now->copy()->startOfYear()->format('Y-m-d'),
                    $now->format('Y-m-d'),
                ];

            case 'last_7_days':
                return [
                    $now->copy()->subDays(6)->format('Y-m-d'),
                    $now->format('Y-m-d'),
                ];

            case 'last_30_days':
                return [
                    $now->copy()->subDays(29)->format('Y-m-d'),
                    $now->format('Y-m-d'),
                ];

            case 'last_90_days':
                return [
                    $now->copy()->subDays(89)->format('Y-m-d'),
                    $now->format('Y-m-d'),
                ];

            default:
                return null;
        }
    }
}
