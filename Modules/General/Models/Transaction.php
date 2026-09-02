<?php

namespace Modules\General\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Employee\Models\Employee;
use Modules\Establishment\Models\Establishment;
use Modules\General\Utils\TransactionUtils;
use Modules\Purchases\Support\PurchasesAccess;
use Modules\Purchases\Support\PurchasesPermissions;
use Modules\Sales\Support\SalesAccess;
use Modules\Sales\Support\SalesPermissions;
use Modules\Sales\Support\TransactionPurpose;
use Modules\Zatca\Models\ZatcaInvoiceSync;
use Modules\Zatca\Support\ZatcaTransactionListBadge;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\TransactionFactory;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'service_fees_payload' => 'array',
        'service_fee_amount' => 'float',
        'service_fee_tax' => 'float',
    ];

    /**
     * Sell documents that count toward sales KPIs (excludes cashier internal consumption).
     */
    public function scopeStandardSalesPurpose(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('purpose')
                ->orWhereNotIn('purpose', TransactionPurpose::internalAliases());
        });
    }

    public static function finalizedStatuses(): array
    {
        return ['approved', 'final'];
    }

    public static function isFinalizedStatus(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), self::finalizedStatuses(), true);
    }

    public function isDraft(): bool
    {
        return ! self::isFinalizedStatus($this->status);
    }

    public function getIsFavoriteAttribute()
    {
        return $this->favorites()->where('user_id', Auth::user()->id)->exists();
    }

    public function favorites()
    {
        return $this->hasMany(FavoriteBills::class);
    }

    public function client()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function establishment()
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    public function sell_lines()
    {
        return $this->hasMany(TransactionSellLine::class, 'transaction_id')->where('is_show', 1);
    }

    public function purchases_lines()
    {
        return $this->hasMany(TransactionePurchasesLine::class, 'transaction_id');
    }

    public function payment()
    {
        return $this->hasMany(TransactionPayments::class, 'transaction_id');
    }

    public function prefixSetting()
    {
        return $this->hasOne(PrefixSetting::class, 'type', 'type');
    }

    public function accountsTransactions()
    {
        return $this->hasOne(AccountingAccountsTransaction::class, 'transaction_id');
    }

    public function zatcaInvoiceSync()
    {
        return $this->hasOne(ZatcaInvoiceSync::class, 'transaction_id');
    }

    public function parentSell()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function isQuotationExpired(): bool
    {
        if ($this->type !== 'quotation' || $this->due_date === null || $this->due_date === '') {
            return false;
        }

        return Carbon::parse($this->due_date)->startOfDay()->lt(now()->startOfDay());
    }

    public static function getsSellsColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'client'],
            ['class' => 'text-start min-w-150px', 'name' => 'transaction_date'],
            ['class' => 'text-start min-w-150px ', 'name' => 'due_date'],
            ['class' => 'text-start min-w-80px ', 'name' => 'payment_status'],
            // ["class" => "text-start min-w-150px", "name" => "total_before_vat"],
            // ["class" => "text-start min-w-150px ", "name" => "vat_value"],
            // ["class" => "text-start min-w-150px  ", "name" => "discount"],
            ['class' => 'text-start min-w-100px  ', 'name' => 'invoice_amount'],
            ['class' => 'text-start min-w-100px  ', 'name' => 'piad_amount'],
            ['class' => 'text-start min-w-100px  ', 'name' => 'remaining_amount'],
        ];
    }

    public static function getsQuotationColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'client'],
            ['class' => 'text-start min-w-150px', 'name' => 'issue_date'],
            ['class' => 'text-start min-w-150px ', 'name' => 'Expiry Date'],
            ['class' => 'text-start min-w-100px', 'name' => 'po_status'],

            ['class' => 'text-start min-w-150px', 'name' => 'total_before_vat'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'amount'],
        ];
    }

    public static function getsPOColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'client'],
            ['class' => 'text-start min-w-150px', 'name' => 'issue_date'],
            ['class' => 'text-start min-w-140px ', 'name' => 'Expiry Date'],
            ['class' => 'text-start min-w-100px', 'name' => 'po_status'],
            ['class' => 'text-start min-w-150px', 'name' => 'total_before_vat'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'amount'],
        ];
    }

    public static function getsPurchasesColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'supplier'],
            ['class' => 'text-start min-w-150px', 'name' => 'transaction_date'],
            ['class' => 'text-start min-w-150px ', 'name' => 'due_date'],
            ['class' => 'text-start min-w-80px ', 'name' => 'payment_status'],
            ['class' => 'text-start min-w-100px  ', 'name' => 'invoice_amount'],
            ['class' => 'text-start min-w-100px  ', 'name' => 'piad_amount'],
            ['class' => 'text-start min-w-100px  ', 'name' => 'remaining_amount'],
        ];
    }

    public static function getSellsTable($transactions)
    {

        $returns = Transaction::whereIn('type', ['sell-return', 'purchases-return'])->pluck('parent_id')->toArray();

        return DataTables::of($transactions)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('ref_no', function ($row) use ($returns) {
                $ref = e($row->ref_no);

                if (($row->type === 'sell' || $row->type === 'purchases') && $row->isDraft()) {
                    $ref .= ' <span class="badge badge-light-warning ms-1">'.e(__('sales::lang.draft')).'</span>';
                }

                if (in_array($row->id, $returns)) {
                    $ref .= '<span class=" m-2" data-bs-toggle="tooltip" title="'.__('general::lang.tooltip_inv_return').'">
                                <i class="fas fa-undo text-danger fs-6"></i>
                            </span>';
                }

                if ($row->type === 'quotation' && $row->isQuotationExpired()) {
                    $ref .= '<span class="ms-2" data-bs-toggle="tooltip" title="'.e(__('sales::lang.quotation_expired_notice')).'">
                                <span class="badge badge-light-danger">'.e(__('sales::lang.quotation_expired')).'</span>
                            </span>';
                }

                $zatcaBadge = ZatcaTransactionListBadge::render($row);
                if ($zatcaBadge !== '') {
                    $ref = '<span class="d-inline-flex align-items-center gap-2 flex-wrap">'.$ref.$zatcaBadge.'</span>';
                }

                return $ref;
            })

            ->editColumn('client', function ($row) {
                return $row->client->name ?? '--';
            })
            ->editColumn('transaction_date', function ($row) {
                return $row->transaction_date ?? '--';
            })
            ->editColumn('due_date', function ($row) {
                if ($row->due_date === null || $row->due_date === '') {
                    return '--';
                }

                $html = e($row->due_date);

                if ($row->type === 'quotation' && $row->isQuotationExpired()) {
                    $html .= ' <span class="badge badge-light-danger">'.e(__('sales::lang.quotation_expired')).'</span>';
                }

                return $html;
            })
            ->editColumn('total_before_tax', function ($row) {
                return $row->total_before_tax ?? '0.00';
            })
            ->editColumn('paid_amount', function ($row) {
                $transactionUtil = new TransactionUtils;

                $paid_amount = $transactionUtil->getTotalPaid($row->id);

                return number_format($paid_amount, 2);
            })
            ->editColumn('remaining_amount', function ($row) {
                $transactionUtil = new TransactionUtils;

                $paid_amount = $transactionUtil->getTotalPaid($row->id);
                $amount = $row->final_total - $paid_amount;
                if ($amount < 0) {
                    $amount = 0;
                }

                return number_format($amount, 2);
            })
            ->editColumn('final_total', function ($row) {
                return number_format($row->final_total, 2) ?? '0.00';
            })
            ->editColumn('payment_status', function ($row) {
                if (($row->type === 'sell' || $row->type === 'purchases') && $row->isDraft()) {
                    return '<span class="text-muted">—</span>';
                }

                if ($row->payment_status == 'paid') {
                    return '<span class="badge badge-light-info px-3 py-3 fs-base">

               '.__('general::lang.paid').' </span>';
                } elseif ($row->payment_status == 'due') {
                    return '<span class="badge badge-light-danger px-3 py-3 fs-base">

               '.__('general::lang.due').' </span>';
                } elseif ($row->payment_status == 'partial') {
                    return '<span class="badge badge-light-success px-3 py-3 fs-base">

           '.__('general::lang.partial').' </span>';
                }
            })
            ->editColumn('po_status', function ($row) {
                if ($row->po_status == 'completed') {
                    return '<span class="badge badge-light-info px-3 py-3 fs-base">

               '.__('general::lang.completed').' </span>';
                } elseif ($row->po_status == 'partial') {
                    return '<span class="badge badge-light-success px-3 py-3 fs-base">

               '.__('general::lang.partial').' </span>';
                } elseif ($row->po_status == 'pending') {
                    return '<span class="badge badge-light-danger px-3 py-3 fs-base">

           '.__('general::lang.pending').' </span>';
                }
            })

            ->addColumn(
                'actions',
                function ($row) {
                    $isSellDraft = $row->type === 'sell' && $row->isDraft();
                    $isPurchasesDraft = $row->type === 'purchases' && $row->isDraft();
                    $isFinalizedSell = $row->type === 'sell' && Transaction::isFinalizedStatus($row->status);

                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">'.__('employee::fields.actions').'<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">';

                    if ($isSellDraft && SalesAccess::can(SalesPermissions::INVOICES_CREATE)) {
                        $actions .= '<div class="menu-item px-3">
                    <a href="'.route('edit-invoice', $row->id).'" class="menu-link px-3">'.e(__('employee::fields.edit')).'</a>
                </div>';
                        $actions .= '<div class="menu-item px-3">
                    <a href="'.route('edit-invoice', $row->id).'" class="menu-link px-3 fw-semibold text-primary">'.e(__('sales::lang.post_as_sales_invoice')).'</a>
                </div>';
                        $actions .= '<div class="menu-item px-3">
                    <a href="#" class="menu-link px-3 text-danger draft-invoice-delete" data-delete-url="'.route('destroy-invoice', $row->id).'" data-ref="'.e($row->ref_no).'">'.e(__('employee::fields.delete')).'</a>
                </div>';
                        $actions .= '<div class="separator my-2"></div>';
                    }

                    if ($isPurchasesDraft && PurchasesAccess::can(PurchasesPermissions::INVOICES_CREATE)) {
                        $actions .= '<div class="menu-item px-3">
                    <a href="'.route('edit-purchases-invoice', $row->id).'" class="menu-link px-3">'.e(__('employee::fields.edit')).'</a>
                </div>';
                        $actions .= '<div class="menu-item px-3">
                    <a href="'.route('edit-purchases-invoice', $row->id).'" class="menu-link px-3 fw-semibold text-primary">'.e(__('purchases::lang.post_as_purchases_invoice')).'</a>
                </div>';
                        $actions .= '<div class="menu-item px-3">
                    <a href="#" class="menu-link px-3 text-danger draft-invoice-delete" data-delete-url="'.route('destroy-purchases-invoice', $row->id).'" data-ref="'.e($row->ref_no).'">'.e(__('employee::fields.delete')).'</a>
                </div>';
                        $actions .= '<div class="separator my-2"></div>';
                    }

                    if (SalesAccess::canTransaction($row, 'show') && PurchasesAccess::canTransaction($row, 'show')) {
                        $actions .= '<div class="menu-item px-3">
                    <a href="'.url("/transaction-show/{$row->id}").'" class="menu-link px-3">'.__('employee::fields.show').'</a>
                </div>';
                    }

                    if (SalesAccess::canTransaction($row, 'print') && PurchasesAccess::canTransaction($row, 'print')) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.url("/transaction-print/{$row->id}").'" class="menu-link px-3">'.__('general.print').'</a>
            </div>';
                    }

                    if ($row->type === 'sell' && ! $isSellDraft && SalesAccess::can(SalesPermissions::INVOICES_CREATE)) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.route('create-invoice', ['duplicate_from' => $row->id, 'type' => 'duplication']).'" class="menu-link px-3">'.e(__('messages.duplicate')).'</a>
            </div>';
                    }

                    if ($row->type === 'purchases' && ! $isPurchasesDraft && PurchasesAccess::can(PurchasesPermissions::INVOICES_CREATE)) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.route('create-purchases-invoice', ['duplicate_from' => $row->id, 'type' => 'duplication']).'" class="menu-link px-3">'.e(__('messages.duplicate')).'</a>
            </div>';
                    }

                    if ($row->type === 'quotation' && SalesAccess::can(SalesPermissions::QUOTATIONS_CREATE)) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.route('create-quotation', ['duplicate_from' => $row->id, 'type' => 'duplication']).'" class="menu-link px-3">'.e(__('messages.duplicate')).'</a>
            </div>';
                    }

                    if ($row->type === 'purchases-order' && PurchasesAccess::can(PurchasesPermissions::ORDERS_CREATE)) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.route('create-purchase-order', ['duplicate_from' => $row->id, 'type' => 'duplication']).'" class="menu-link px-3">'.e(__('messages.duplicate')).'</a>
            </div>';
                    }

                    $completedReturn = Transaction::where('parent_id', $row->id)->where('type', 'sell-return')->where('po_status', 'completed')->first();

                    if ($isFinalizedSell && ! $completedReturn && SalesAccess::can([SalesPermissions::CREATE_INVOICE_RETURN, SalesPermissions::RETURNS_CREATE])) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.url("/create-sell-return/{$row->id}").'" class="menu-link px-3">'.__('general::lang.sell-return').'</a>
            </div>';
                    }

                    if ($row->type == 'purchases' && ! $isPurchasesDraft && PurchasesAccess::can([PurchasesPermissions::CREATE_INVOICE_RETURN, PurchasesPermissions::RETURNS_CREATE])) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.url("/create-purchases-return/{$row->id}").'" class="menu-link px-3">'.__('general::lang.purchases-return').'</a>
            </div>';
                    }

                    if (($row->type == 'sell-return' || $row->type == 'purchases-return') && $row->parent_id) {
                        $canReference = $row->type === 'sell-return'
                            ? SalesAccess::can(SalesPermissions::REFERENCE_INVOICE_SHOW)
                            : PurchasesAccess::can(PurchasesPermissions::REFERENCE_INVOICE_SHOW);
                        if ($canReference) {
                            $actions .= '<div class="menu-item px-3">
                <a href="'.url("/transaction-print/{$row->parent_id}").'" class="menu-link px-3">'.__('general::lang.transaction-parent').'</a>
            </div>';
                        }
                    }

                    if ($row->type != 'quotation' && $row->type != 'purchases-order' && ! $isSellDraft && ! $isPurchasesDraft) {
                        if ($row->payment_status == 'due' || $row->payment_status == 'partial') {
                            if (SalesAccess::canAddPayment($row) && PurchasesAccess::canAddPayment($row)) {
                                $actions .= '<div class="menu-item px-3">
                    <a href="'.url("/transaction-show-payments/{$row->id}").'" class="menu-link px-3">'.__('general::lang.add_payment').'</a>
                </div>';
                            } elseif (SalesAccess::canShowPayments($row) && PurchasesAccess::canShowPayments($row)) {
                                $actions .= '<div class="menu-item px-3">
                    <a href="'.url("/transaction-show-payments/{$row->id}").'" class="menu-link px-3">'.__('general::lang.show_payment').'</a>
                </div>';
                            }
                        } elseif (SalesAccess::canShowPayments($row) && PurchasesAccess::canShowPayments($row)) {
                            $actions .= '<div class="menu-item px-3">
                    <a href="'.url("/transaction-show-payments/{$row->id}").'" class="menu-link px-3">'.__('general::lang.show_payment').'</a>
                </div>';
                        }
                    }

                    // $status = $row->status == 'active' ? __('messages.deactivate') : __('messages.activate');

                    // $actions .= '<div class="menu-item px-3">
                    //     <a href="' . url("/client-update-status/{$row->id}") . '" class="menu-link px-3">' . $status . '</a>
                    // </div>';

                    //     $actions .= '<div class="menu-item px-3">
                    //     <a href="' . url("/client-destroy/{$row->id}") . '" class="menu-link px-3">' . __('employee::fields.delete') . '</a>
                    // </div>';

                    // $actions .= '<div class="menu-item px-3">
                    //                 <a class="menu-link px-3 delete-btn" href="' . url("/client-destroy/{$row->id}") . '" data-id="' . $row->id . '"  data-ref_no="' . $row->name . '">'. __('employee::fields.delete') . '</a>
                    //             </div>';

                    return $actions;
                }
            )

            ->rawColumns(['actions', 'po_status', 'payment_status', 'ref_no', 'due_date', 'remaining_amount', 'paid_amount', 'client', 'id'])
            ->make(true);
    }
}
