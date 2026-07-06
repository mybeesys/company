<?php

namespace Modules\Accounting\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Opening-balance vouchers dated on the report start are opening, not period movement.
 */
class AccountingOpeningBalanceScope
{
    public const SUB_TYPE = 'opening_balance';

    public static function openingConditionSql(string $operationDateColumn = 't.operation_date'): string
    {
        $date = "DATE({$operationDateColumn})";

        return "({$date} < DATE(?) OR ({$date} = DATE(?) AND t.sub_type = '".self::SUB_TYPE."'))";
    }

    public static function periodConditionSql(string $operationDateColumn = 't.operation_date'): string
    {
        $date = "DATE({$operationDateColumn})";

        return "({$date} >= DATE(?) AND {$date} <= DATE(?) AND NOT ({$date} = DATE(?) AND t.sub_type = '".self::SUB_TYPE."'))";
    }

    public static function applyOpeningScope(Builder $query, string $startDate, string $dateColumn = 'operation_date'): Builder
    {
        return $query->where(function (Builder $q) use ($startDate, $dateColumn) {
            $q->where($dateColumn, '<', $startDate)
                ->orWhere(function (Builder $inner) use ($startDate, $dateColumn) {
                    $inner->whereDate($dateColumn, $startDate)
                        ->where('sub_type', self::SUB_TYPE);
                });
        });
    }

    public static function applyExcludeOpeningOnStartFromPeriod(Builder $query, string $startDate, string $dateColumn = 'operation_date'): Builder
    {
        return $query->where(function (Builder $q) use ($startDate, $dateColumn) {
            $q->where('sub_type', '!=', self::SUB_TYPE)
                ->orWhereDate($dateColumn, '!=', $startDate);
        });
    }
}
