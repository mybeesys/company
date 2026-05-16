<?php

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Yajra\DataTables\Facades\DataTables;

class ExpenseCategory extends Model
{
    protected $guarded = ['id'];

    protected $table = 'expense_categories';

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    /** @return array<int, array{class: string, name: string}> */
    public static function manageTableColumns(): array
    {
        return [
            ['class' => 'text-start min-w-200px', 'name' => 'category_name'],
            ['class' => 'text-center min-w-100px', 'name' => 'expenses_count'],
            ['class' => 'text-end min-w-120px', 'name' => 'net_total'],
        ];
    }

    public static function manageQuery(): Builder
    {
        return static::query()
            ->withCount('expenses')
            ->selectRaw('expense_categories.*, (
                SELECT COALESCE(SUM(CASE WHEN tax > 0 THEN amount - tax ELSE amount END), 0)
                FROM expenses
                WHERE expenses.expense_category_id = expense_categories.id
            ) as net_total_sum');
    }

    public static function manageDataTable(Builder $query): mixed
    {
        return DataTables::eloquent($query)
            ->editColumn('id', fn ($row) => "<div class='badge badge-light-info'>{$row->id}</div>")
            ->addColumn('category_name', fn ($row) => '<span class="fw-semibold text-gray-800">'.e($row->name).'</span>')
            ->addColumn('expenses_count', fn ($row) => (string) ($row->expenses_count ?? 0))
            ->addColumn('net_total', fn ($row) => number_format((float) ($row->net_total_sum ?? 0), 2))
            ->addColumn('actions', function ($row) {
                $destroyUrl = route('expenses.categories.destroy', $row->id);

                $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">';
                $actions .= e(__('employee::fields.actions')).'<i class="ki-outline ki-down fs-5 ms-1"></i></a>';
                $actions .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">';
                $actions .= '<div class="menu-item px-3"><a href="#" class="menu-link px-3 expense-category-edit" data-id="'.$row->id.'" data-name="'.e($row->name).'">'.e(__('employee::fields.edit')).'</a></div>';
                $actions .= '<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger expense-category-delete" data-url="'.e($destroyUrl).'">'.e(__('accounting::lang.voucher_delete')).'</a></div>';
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['id', 'category_name', 'actions'])
            ->orderColumn('category_name', 'name $1')
            ->orderColumn('id', 'id $1')
            ->filter(function ($query) {
                $kw = request()->input('search.value');
                if (! is_string($kw)) {
                    return;
                }
                $kw = trim($kw);
                if ($kw === '') {
                    return;
                }
                $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $kw).'%';
                $query->where('expense_categories.name', 'like', $like);
            })
            ->make(true);
    }
}
