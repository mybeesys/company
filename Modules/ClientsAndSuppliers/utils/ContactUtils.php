<?php

namespace Modules\ClientsAndSuppliers\utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Support\AccountingNote;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\AutoJournalGuard;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;

class ContactUtils
{
    /**
     * Post excess customer receipt as a real receipt voucher:
     * Dr cash/bank, Cr customer AR — and store a TransactionPayments row so it appears in receipts.
     */
    public function addRemainingAmountToCustomerAccount($customerId, $amount, $transaction = null, $request = null)
    {
        return $this->postContactAdvanceReceipt(
            contactId: (int) $customerId,
            amount: (float) $amount,
            isSupplier: false,
            linkedTransaction: $transaction,
            request: $request
        );
    }

    /**
     * Post excess supplier payment as a real payment voucher:
     * Dr supplier AP, Cr cash/bank — and store a TransactionPayments row.
     */
    public function addRemainingAmountToSupplierAccount($supplierId, $amount, $transaction = null, $request = null)
    {
        return $this->postContactAdvanceReceipt(
            contactId: (int) $supplierId,
            amount: (float) $amount,
            isSupplier: true,
            linkedTransaction: $transaction,
            request: $request
        );
    }

    private function postContactAdvanceReceipt(
        int $contactId,
        float $amount,
        bool $isSupplier,
        mixed $linkedTransaction = null,
        mixed $request = null
    ): bool {
        $contact = Contact::find($contactId);
        if (! $contact || ! $contact->account_id) {
            return false;
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            return false;
        }

        $cashAccountId = (int) data_get($request, 'account_id', 0);
        if ($cashAccountId <= 0) {
            throw ValidationException::withMessages([
                'account_id' => __('sales::lang.receipt_payment_account_invalid'),
            ]);
        }

        if ($cashAccountId === (int) $contact->account_id) {
            throw ValidationException::withMessages([
                'account_id' => __('sales::lang.receipt_payment_account_same_as_contact'),
            ]);
        }

        $paymentOn = Carbon::parse(data_get($request, 'payment_on', now()))->format('Y-m-d H:i:s');
        FiscalPeriodGatekeeper::assertPostable($paymentOn);

        $transactionUtil = new TransactionUtils;
        $paymentRef = $transactionUtil->generateReferenceNumber(
            $isSupplier ? 'purchase_payment' : 'sell_payment'
        );

        $advanceLabel = $isSupplier ? 'رصيد متبقي للمورد' : 'رصيد متبقي للعميل';
        $voucherLabel = $isSupplier
            ? __('accounting::lang.payment_voucher')
            : __('accounting::lang.receipt_voucher');
        $subType = $isSupplier ? 'payment_voucher' : 'receipt_voucher';

        $note = AccountingNote::compose(
            $voucherLabel,
            $advanceLabel,
            $contact->name
        );

        $extraNote = trim((string) data_get($request, 'additionalNotes', ''));
        if ($extraNote !== '') {
            $note = trim($note.' — '.$extraNote);
        }

        $payment = TransactionPayments::create([
            // Advance / overpayment receipt is not tied to a single invoice.
            'transaction_id' => null,
            // Used by sales receipts index filter so the voucher appears in the list.
            'payment_type' => 'debit',
            'amount' => $amount,
            'method' => 'cash',
            'is_return' => 0,
            'note' => $note,
            'paid_on' => $paymentOn,
            'created_by' => Auth::id() ?? 1,
            'payment_for' => $contactId,
            'payment_ref_no' => $paymentRef,
            'account_id' => $cashAccountId,
        ]);

        $mapping = new AccountingAccTransMapping;
        $mapping->ref_no = AccountingUtil::generateReferenceNumber('journal_entry');
        $mapping->note = $note;
        $mapping->type = 'journal_entry';
        $mapping->created_by = Auth::id() ?? 1;
        $mapping->is_manual = 0;
        $mapping->operation_date = $paymentOn;
        $mapping->save();

        $costCenterId = data_get($request, 'cost_center_id') ?: data_get($request, 'cost_center');
        $costCenterId = $costCenterId ? (int) $costCenterId : null;
        $createdBy = Auth::id() ?? 1;

        $customerLineType = $isSupplier ? 'debit' : 'credit';
        $cashLineType = $isSupplier ? 'credit' : 'debit';

        AccountingAccountsTransaction::create([
            'amount' => $amount,
            'accounting_account_id' => (int) $contact->account_id,
            'type' => $customerLineType,
            'sub_type' => $subType,
            'note' => $note,
            'operation_date' => $paymentOn,
            'created_by' => $createdBy,
            'transaction_id' => $linkedTransaction->id ?? null,
            'transaction_payment_id' => $payment->id,
            'acc_trans_mapping_id' => $mapping->id,
            'cost_center_id' => $costCenterId,
        ]);

        AccountingAccountsTransaction::create([
            'amount' => $amount,
            'accounting_account_id' => $cashAccountId,
            'type' => $cashLineType,
            'sub_type' => $subType,
            'note' => $note,
            'operation_date' => $paymentOn,
            'created_by' => $createdBy,
            'transaction_id' => $linkedTransaction->id ?? null,
            'transaction_payment_id' => $payment->id,
            'acc_trans_mapping_id' => $mapping->id,
            'cost_center_id' => $costCenterId,
        ]);

        AutoJournalGuard::assertBalanced((int) $mapping->id);

        return true;
    }
}
