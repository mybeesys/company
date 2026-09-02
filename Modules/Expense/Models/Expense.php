<?php

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Support\AccountingAccess;
use Modules\Accounting\Support\AccountingPermissions;
use Modules\General\Models\Tax;
use Yajra\DataTables\Facades\DataTables;

class Expense extends Model
{
    protected $guarded = ['id'];

    protected $table = 'expenses';

    protected $appends = [
        'net_amount',
        'gross_amount',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:6',
            'tax' => 'decimal:6',
            'date' => 'date',
            'attributes' => 'array',
            'meta' => 'array',
            'tax_profile_data' => 'array',
        ];
    }

    /** Stored DB gross when tax > 0, else stored net/full amount */
    public function storedAmount(): float
    {
        return (float) ($this->attributes['amount'] ?? 0);
    }

    /** Reading semantics per spec: net when taxed */
    public function getNetAmountAttribute(): float
    {
        $raw = $this->storedAmount();
        $tax = (float) ($this->attributes['tax'] ?? 0);

        if ($tax > 0) {
            return round($raw - $tax, 6);
        }

        return $raw;
    }

    public function getGrossAmountAttribute(): float
    {
        return $this->storedAmount();
    }

    public function getTotalAttribute(): float
    {
        $tax = (float) ($this->attributes['tax'] ?? 0);

        if ($tax > 0) {
            return round($this->storedAmount(), 6);
        }

        return $this->storedAmount();
    }

    public function appliedTax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'debit_accounting_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(AccountingAccount::class, 'credit_accounting_account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class);
    }

    public function journalMapping(): BelongsTo
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'acc_trans_mapping_id');
    }

    public function invoiceLinked(): bool
    {
        return ! empty($this->meta['invoice_id']);
    }

    /** @return array<int, array{class: string, name: string}> */
    public static function manageTableColumns(): array
    {
        return [
            ['class' => 'text-start min-w-125px', 'name' => 'expense_date'],
            ['class' => 'text-start min-w-160px', 'name' => 'debit_account'],
            ['class' => 'text-start min-w-200px', 'name' => 'credit_account'],
            ['class' => 'text-start min-w-120px', 'name' => 'cost_center'],
            ['class' => 'text-start min-w-220px', 'name' => 'description'],
            ['class' => 'text-end min-w-100px', 'name' => 'net_amount'],
            ['class' => 'text-end min-w-100px', 'name' => 'tax_amount'],
            ['class' => 'text-end min-w-100px', 'name' => 'gross_amount'],
            ['class' => 'text-center min-w-90px', 'name' => 'attachments'],
            ['class' => 'text-start min-w-150px', 'name' => 'created_at'],
        ];
    }

    public static function accountLabel(?AccountingAccount $acc): string
    {
        if (! $acc) {
            return '—';
        }
        $nm = app()->getLocale() === 'ar' ? $acc->name_ar : $acc->name_en;

        return '<div class="fw-semibold text-gray-800">'.e($nm).'</div><div class="text-muted fs-8">'.e((string) $acc->gl_code).'</div>';
    }

    public static function manageDataTable(Builder $query): mixed
    {
        return DataTables::eloquent($query)
            ->editColumn('id', fn ($row) => "<div class='badge badge-light-info'>{$row->id}</div>")
            ->addColumn('expense_date', fn ($row) => optional($row->date)->format('j M Y') ?? '—')
            ->addColumn('debit_account', fn ($row) => static::accountLabel($row->debitAccount))
            ->addColumn('credit_account', fn ($row) => static::accountLabel($row->creditAccount))
            ->addColumn('cost_center', function ($row) {
                $cc = $row->costCenter;
                if (! $cc) {
                    return '—';
                }
                $nm = app()->getLocale() === 'ar' ? $cc->name_ar : $cc->name_en;

                return '<span class="text-gray-800">'.e($nm).'</span>';
            })
            ->addColumn('description', fn ($row) => e(Str::limit(strip_tags((string) ($row->description ?? '')), 55)))
            ->addColumn('net_amount', fn ($row) => number_format($row->net_amount, 2))
            ->addColumn('tax_amount', fn ($row) => number_format((float) $row->getRawOriginal('tax'), 2))
            ->addColumn('gross_amount', fn ($row) => number_format($row->total, 2))
            ->addColumn('attachments', fn ($row) => (string) $row->attachments->count())
            ->addColumn('created_at', fn ($row) => $row->created_at?->format('j M Y H:i') ?? '—')
            ->addColumn('actions', function ($row) {
                $showUrl = route('expenses.manage.show', $row->id);
                $editUrl = route('expenses.manage.edit', $row->id);
                $dupUrl = route('expenses.manage.create', ['duplicate_from' => $row->id]);
                $destroyUrl = route('expenses.manage.destroy', $row->id);

                $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">';
                $actions .= e(__('employee::fields.actions')).'<i class="ki-outline ki-down fs-5 ms-1"></i></a>';
                $actions .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">';
                $actions .= '<div class="menu-item px-3"><a href="'.e($showUrl).'" class="menu-link px-3">'.e(__('accounting::lang.voucher_show')).'</a></div>';
                if (AccountingAccess::can(AccountingPermissions::EXPENSES_UPDATE)) {
                    $actions .= '<div class="menu-item px-3"><a href="'.e($editUrl).'" class="menu-link px-3">'.e(__('employee::fields.edit')).'</a></div>';
                }
                if (AccountingAccess::can(AccountingPermissions::EXPENSES_CREATE)) {
                    $actions .= '<div class="menu-item px-3"><a href="'.e($dupUrl).'" class="menu-link px-3">'.e(__('accounting::fields.duplication')).'</a></div>';
                }
                if (AccountingAccess::can(AccountingPermissions::EXPENSES_DELETE)) {
                    $actions .= '<div class="menu-item px-3"><a href="#" class="menu-link px-3 text-danger expense-manage-delete" data-url="'.e($destroyUrl).'">'.e(__('accounting::lang.voucher_delete')).'</a></div>';
                }
                $actions .= '</div>';

                return $actions;
            })
            ->rawColumns(['id', 'debit_account', 'credit_account', 'cost_center', 'actions'])
            ->orderColumn('expense_date', 'date $1')
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
                        })
                        ->orWhereHas('costCenter', function ($c) use ($like) {
                            $c->where('name_ar', 'like', $like)
                                ->orWhere('name_en', 'like', $like);
                        });
                });
            })
            ->make(true);
    }
}
