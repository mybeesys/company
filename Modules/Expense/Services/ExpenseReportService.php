<?php

namespace Modules\Expense\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Expense\Models\Expense;

final class ExpenseReportService
{
    public static function filteredQuery(Request $request): Builder
    {
        $query = Expense::query();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }

        $debitIds = array_values(array_filter(array_map('intval', (array) $request->input('debit_account_ids', []))));
        if ($debitIds !== []) {
            $query->whereIn('debit_accounting_account_id', $debitIds);
        }

        $creditIds = array_values(array_filter(array_map('intval', (array) $request->input('credit_account_ids', []))));
        if ($creditIds !== []) {
            $query->whereIn('credit_accounting_account_id', $creditIds);
        }

        $costCenterIds = array_values(array_filter(array_map('intval', (array) $request->input('cost_center_ids', []))));
        if ($costCenterIds !== []) {
            $query->whereIn('cost_center_id', $costCenterIds);
        }

        if ($request->boolean('with_attachments')) {
            $query->has('attachments');
        }

        $taxId = $request->input('tax_id');
        if ($taxId !== null && $taxId !== '' && $taxId !== 'all') {
            if ($taxId === 'none') {
                $query->where(function ($q) {
                    $q->whereNull('tax_id')->orWhere('tax', '<=', 0);
                });
            } else {
                $query->where('tax_id', (int) $taxId);
            }
        }

        $keyword = trim((string) $request->input('q', ''));
        if ($keyword !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $keyword).'%';
            $query->where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                    ->orWhereHas('debitAccount', function ($a) use ($like) {
                        $a->where('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('gl_code', 'like', $like);
                    })
                    ->orWhereHas('creditAccount', function ($a) use ($like) {
                        $a->where('name_ar', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('gl_code', 'like', $like);
                    });
            });
        }

        return $query;
    }

    /** @return array{count: int, net: float, tax: float, gross: float} */
    public static function summarize(Builder $query): array
    {
        $agg = (clone $query)
            ->selectRaw('COUNT(*) as expense_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(tax), 0) as tax_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN tax > 0 THEN amount - tax ELSE amount END), 0) as net_total')
            ->first();

        return [
            'count' => (int) ($agg->expense_count ?? 0),
            'gross' => (float) ($agg->gross_total ?? 0),
            'tax' => (float) ($agg->tax_total ?? 0),
            'net' => (float) ($agg->net_total ?? 0),
        ];
    }

    public static function accountBreakdown(Builder $query): Collection
    {
        $table = (new Expense)->getTable();
        $localeAr = app()->getLocale() === 'ar';

        return (clone $query)
            ->join('accounting_accounts as aa', 'aa.id', '=', "{$table}.debit_accounting_account_id")
            ->groupBy('aa.id', 'aa.gl_code', 'aa.name_ar', 'aa.name_en')
            ->selectRaw('aa.id as account_id')
            ->selectRaw('aa.gl_code as account_gl_code')
            ->selectRaw($localeAr ? 'aa.name_ar as account_name' : 'aa.name_en as account_name')
            ->selectRaw('COUNT(*) as expense_count')
            ->selectRaw('COALESCE(SUM(amount), 0) as gross_total')
            ->selectRaw('COALESCE(SUM(tax), 0) as tax_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN tax > 0 THEN amount - tax ELSE amount END), 0) as net_total')
            ->orderByDesc('gross_total')
            ->get();
    }

    /** @return array<string, mixed> */
    public static function dataset(Request $request): array
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $request->merge([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $baseQuery = static::filteredQuery($request);
        $summary = static::summarize($baseQuery);
        $expenses = (clone $baseQuery)
            ->with(['debitAccount', 'creditAccount', 'costCenter', 'appliedTax'])
            ->withCount('attachments')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
        $byAccount = static::accountBreakdown($baseQuery);

        return compact('startDate', 'endDate', 'summary', 'expenses', 'byAccount', 'baseQuery');
    }
}
