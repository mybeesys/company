<?php

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\Employee\Models\Employee;
use Modules\General\Models\Transaction;
use Modules\Accounting\Support\AccountingNote;
use Modules\General\Models\TransactionPayments;
use Modules\Accounting\Support\AccountingAccess;
use Modules\Accounting\Support\AccountingPermissions;
use Yajra\DataTables\Facades\DataTables;

// use Modules\Accounting\Database\Factories\AccountingAccountsTransactionFactory;

class AccountingAccountsTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $table = 'accounting_accounts_transactions';

    public function setNoteAttribute(mixed $value): void
    {
        $this->attributes['note'] = AccountingNote::normalizeForStorage($value);
    }

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    public function accTransMapping()
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'acc_trans_mapping_id');
    }

    public function transactionPayments()
    {
        return $this->belongsTo(TransactionPayments::class, 'transaction_payment_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function costCenter()
    {
        return $this->belongsTo(AccountingCostCenter::class, 'cost_center_id');
    }

    /**
     * Human-readable reference for ledger / cost-center reports (journal ref, invoice ref, or payment ref).
     */
    public function displayRefNo(): string
    {
        $ref = $this->accTransMapping?->ref_no
            ?? $this->transaction?->ref_no
            ?? $this->transactionPayments?->payment_ref_no;

        return $ref ? (string) $ref : '—';
    }

    /**
     * URL to open the originating document for this ledger line (invoice, payment receipt, standalone voucher, manual journal).
     */
    public function ledgerDetailUrl(): ?string
    {
        if ($this->transaction_payment_id) {
            return route('show-receipts-payments', ['id' => $this->transaction_payment_id]);
        }

        $sub = (string) ($this->sub_type ?? '');

        if ($sub === 'receipt_voucher' && ! $this->transaction_payment_id) {
            return route('receipt-vouchers-show', ['id' => $this->id]);
        }

        if ($sub === 'payment_voucher' && ! $this->transaction_payment_id) {
            return route('payment-vouchers-show', ['id' => $this->id]);
        }

        $tx = $this->transaction;
        if ($tx) {
            return route('transaction-show', ['id' => $tx->id]);
        }

        if ($this->acc_trans_mapping_id && in_array($sub, ['journal_entry', 'manual_journal'], true)) {
            return route('journal-entry-show', ['id' => $this->acc_trans_mapping_id]);
        }

        return null;
    }

    /**
     * Standalone receipt/payment vouchers store two paired rows in accounting_accounts_transactions.
     * Invoice payment journals reuse the same sub_type labels but set transaction_payment_id; they must
     * not appear in the standalone voucher UI (pairing uses transaction_id differently).
     */
    public function scopeStandaloneVoucherSubType($query, string $subType)
    {
        return $query->where('sub_type', $subType)->whereNull('transaction_payment_id');
    }

    public static function getAccountTransactionType($tansaction_type)
    {
        $account_transaction_types = [
            'sell' => 'credit',
            'purchases' => 'debit',
            'expense' => 'debit',
            'purchase_return' => 'credit',
            'sell_return' => 'debit',
            'payroll' => 'debit',
            'expense_refund' => 'credit',
            'hms_booking' => 'credit',
        ];

        return $account_transaction_types[$tansaction_type];
    }

    public static function getReceiptsColumns()
    {
        return [

            ['class' => 'text-start min-w-150px ', 'name' => 'account'],
            ['class' => 'text-start min-w-150px ', 'name' => 'debit/credit'],
            ['class' => 'text-start min-w-150px', 'name' => 'operation_date'],
            ['class' => 'text-start min-w-80px ', 'name' => 'amount'],
            ['class' => 'text-start min-w-150px  ', 'name' => 'created_by'],
            ['class' => 'text-start min-w-150px ', 'name' => 'note'],
        ];
    }

    public static function getReceiptsTable($transactions, string $voucherSubType = 'receipt_voucher')
    {
        $isPayment = $voucherSubType === 'payment_voucher';
        $editClass = $isPayment ? 'payment-voucher-edit' : 'receipt-voucher-edit';
        $dupClass = $isPayment ? 'payment-voucher-duplicate' : 'receipt-voucher-duplicate';
        $delClass = $isPayment ? 'payment-voucher-delete' : 'receipt-voucher-delete';
        $showUrlBase = $isPayment ? url('payment-vouchers') : url('receipt-vouchers');

        return DataTables::of($transactions)
            ->editColumn('id', function ($row) {
                return "<div class='badge badge-light-info'>
                                     {$row->id}
                            </div>";
            })
            ->editColumn('account', function ($row) {
                return $row->account->gl_code.' - '.(App::getLocale() == 'en' ? $row->account->name_en : $row->account->name_ar);
            })
            ->editColumn('operation_date', function ($row) {
                return $row->operation_date ?? '--';
            })
            ->editColumn('type', function ($row) {
                return __('accounting::lang.'.$row->type);
            })

            ->editColumn('amount', function ($row) {
                return $row->amount;
            })
            ->editColumn('created_by', function ($row) {
                return $row->createdBy->name;
            })

            ->editColumn('note', function ($row) {
                return $row->note ?? '--';
            })

            ->addColumn(
                'actions',
                function ($row) use ($editClass, $dupClass, $delClass, $showUrlBase, $isPayment) {
                    $updatePerm = $isPayment ? AccountingPermissions::PAYMENT_UPDATE : AccountingPermissions::RECEIPT_UPDATE;
                    $createPerm = $isPayment ? AccountingPermissions::PAYMENT_CREATE : AccountingPermissions::RECEIPT_CREATE;
                    $deletePerm = $isPayment ? AccountingPermissions::PAYMENT_DELETE : AccountingPermissions::RECEIPT_DELETE;

                    $actions = '<a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">'.__('employee::fields.actions').'<i class="ki-outline ki-down fs-5 ms-1"></i></a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-175px py-4" data-kt-menu="true">';

                    $actions .= '<div class="menu-item px-3">
                        <a href="#" class="menu-link px-3 voucher-show-btn" data-voucher-url="'.$showUrlBase.'/'.$row->id.'/modal'.'" data-line-id="'.$row->id.'">'.__('accounting::lang.voucher_show').'</a>
                    </div>';
                    if (AccountingAccess::can($updatePerm)) {
                        $actions .= '<div class="menu-item px-3">
                        <a href="#" class="menu-link px-3 '.$editClass.'" data-line-id="'.$row->id.'">'.__('employee::fields.edit').'</a>
                    </div>';
                    }
                    if (AccountingAccess::can($createPerm)) {
                        $actions .= '<div class="menu-item px-3">
                        <a href="#" class="menu-link px-3 '.$dupClass.'" data-line-id="'.$row->id.'">'.__('accounting::fields.duplication').'</a>
                    </div>';
                    }
                    if (AccountingAccess::can($deletePerm)) {
                        $actions .= '<div class="menu-item px-3">
                        <a href="#" class="menu-link px-3 text-danger '.$delClass.'" data-line-id="'.$row->id.'">'.__('accounting::lang.voucher_delete').'</a>
                    </div>';
                    }

                    return $actions;
                }
            )

            ->rawColumns(['actions', 'account', 'type', 'id'])
            ->make(true);
    }
}
