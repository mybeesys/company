<?php

namespace Modules\General\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Purchases\Support\PurchasesAccess;
use Modules\Sales\Support\SalesAccess;
use Modules\Sales\Utils\SalesUtile;
use Yajra\DataTables\Facades\DataTables;

// use Modules\General\Database\Factories\TransactionPaymentsFactory;

class TransactionPayments extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'account_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function displayPaymentMethod(): ?string
    {
        if ($this->paymentMethod) {
            return app()->getLocale() === 'ar'
                ? $this->paymentMethod->name_ar
                : $this->paymentMethod->name_en;
        }

        if (! $this->method) {
            return null;
        }

        $labels = SalesUtile::paymentMethods();

        return $labels[$this->method]
            ?? (__('general::lang.'.$this->method) !== 'general::lang.'.$this->method
                ? __('general::lang.'.$this->method)
                : $this->method);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function client()
    {
        return $this->belongsTo(Contact::class, 'payment_for');
    }

    /**
     * Prefill values for "new receipt" form when duplicating from an existing payment (no invoices copied).
     *
     * @param  bool  $forSupplierContext  true = create-suppliers-receipts, false = create-receipts
     */
    public static function formDefaultsFromPaymentForDuplicate(?int $paymentId, bool $forSupplierContext): ?array
    {
        if (! $paymentId) {
            return null;
        }

        $payment = self::with('transaction')->find($paymentId);
        if (! $payment || ! $payment->transaction) {
            return null;
        }

        $transaction = $payment->transaction;
        $isSupplierInvoice = in_array($transaction->type, ['purchases', 'purchase'], true);
        if ($forSupplierContext !== $isSupplierInvoice) {
            return null;
        }

        if (! $payment->account_id) {
            return null;
        }

        $costCenterId = AccountingAccountsTransaction::query()
            ->where('transaction_payment_id', $payment->id)
            ->whereNotNull('cost_center_id')
            ->value('cost_center_id');

        return [
            'client_id' => (int) $payment->payment_for,
            'account_id' => (int) $payment->account_id,
            'payment_on' => \Carbon\Carbon::parse($payment->paid_on)->format('Y-m-d'),
            'paid_amount' => (float) str_replace(',', '', (string) $payment->amount),
            'cost_center_id' => $costCenterId ? (int) $costCenterId : null,
            'additional_notes' => (string) ($payment->note ?? ''),
        ];
    }

    public static function getReceiptsColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px', 'name' => 'paid_on'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'transaction_ref_no'],
            ['class' => 'text-start min-w-150px ', 'name' => 'client'],
            ['class' => 'text-start min-w-80px ', 'name' => 'piad_amount'],
        ];
    }

    public static function getSuppliersReceiptsColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'ref_no'],
            ['class' => 'text-start min-w-150px', 'name' => 'paid_on'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'transaction_ref_no'],
            ['class' => 'text-start min-w-150px ', 'name' => 'supplier'],
            ['class' => 'text-start min-w-80px ', 'name' => 'piad_amount'],
        ];
    }

    public static function getReceiptsTable($transactions)
    {
        $dt = $transactions instanceof Builder
            ? DataTables::eloquent($transactions)
            : DataTables::of($transactions);

        return $dt
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('payment_ref_no', function ($row) {
                return $row->payment_ref_no;
            })
            ->editColumn('paid_on', function ($row) {
                return $row->paid_on ?? '--';
            })
            ->editColumn('transaction_ref_no', function ($row) {
                $tx = $row->transaction;
                if (! $tx) {
                    return '--';
                }

                return '<a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6" href="'.url("/transaction-show/{$tx->id}").'">'.$tx->ref_no.'</a>';
            })
            ->editColumn('client', function ($row) {
                return $row->client->name ?? '--';
            })

            ->editColumn('amount', function ($row) {
                return $row->amount ?? '0.00';
            })

            ->addColumn(
                'actions',
                function ($row) {
                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">'.__('employee::fields.actions').'<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-200px py-4" data-kt-menu="true">';

                    if (SalesAccess::canReceipt($row, 'show') && PurchasesAccess::canReceipt($row, 'show')) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.url("/show-receipts-payments/{$row->id}").'" class="menu-link px-3">'.__('messages.view').'</a>
            </div>';
                    }

                    $editUrl = route('receipts-payments.edit', $row);
                    $dupUrl = route('receipts-payments.duplicate', $row);
                    $delUrl = route('receipts-payments.destroy', $row);
                    $csrf = csrf_token();
                    $swalTitle = e(__('sales::lang.delete_receipt_confirm_title'));
                    $swalText = e(__('sales::lang.confirm_delete_receipt_payment'));
                    $swalConfirm = e(__('sales::lang.delete_receipt_confirm_btn'));
                    $swalCancel = e(__('messages.cancel'));

                    if (SalesAccess::canReceipt($row, 'update') && PurchasesAccess::canReceipt($row, 'update')) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.$editUrl.'" class="menu-link px-3">'.__('messages.edit').'</a>
            </div>';
                    }
                    if (SalesAccess::canReceipt($row, 'create') && PurchasesAccess::canReceipt($row, 'create')) {
                        $actions .= '<div class="menu-item px-3">
                <a href="'.$dupUrl.'" class="menu-link px-3">'.__('messages.duplicate').'</a>
            </div>';
                    }
                    if (SalesAccess::canReceipt($row, 'delete') && PurchasesAccess::canReceipt($row, 'delete')) {
                        $actions .= '<div class="menu-item px-3">
                <form method="post" action="'.$delUrl.'" class="px-0 js-delete-receipt-payment-form">'
                        .'<input type="hidden" name="_token" value="'.$csrf.'">'
                        .'<input type="hidden" name="_method" value="DELETE">'
                        .'<button type="button" class="menu-link px-3 border-0 bg-transparent w-100 text-start text-danger js-receipt-delete-submit"'
                        .' data-swal-title="'.$swalTitle.'"'
                        .' data-swal-text="'.$swalText.'"'
                        .' data-swal-confirm="'.$swalConfirm.'"'
                        .' data-swal-cancel="'.$swalCancel.'"'
                        .'>'
                        .__('messages.delete').'</button>'
                    .'</form>
            </div>';
                    }

                    $actions .= '</div>';

                    return $actions;
                }
            )

            ->rawColumns(['actions', 'transaction_ref_no', 'client', 'id'])
            ->make(true);
    }
}
