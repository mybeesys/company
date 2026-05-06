<?php

namespace Modules\ClientsAndSuppliers\utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;

class ContactUtils
{
    // public function addRemainingAmountToCustomerAccount($customerId, $amount, $transaction = null)
    // {
    //     $customer = Contact::find($customerId);
    //     $transactionPayment = null;
    //     if ($customer) {

    //         if (empty($ref_number)) {

    //             $ref_number = AccountingUtil::generateReferenceNumber('contact_balance');
    //         }

    //         $acc_trans_mapping = new AccountingAccTransMapping();

    //         $type = '';
    //         $aac_tranasction_type = 'credit';
    //         if (isset($transaction->type)) {

    //             $type = $transaction->type;
    //             $aac_tranasction_type = AccountingAccountsTransaction::getAccountTransactionType($type);
    //         }

    //         $acc_trans_mapping->ref_no = $ref_number;
    //         $acc_trans_mapping->note = isset($request->additionalNotes) ?? "";
    //         $acc_trans_mapping->type = $type;
    //         $acc_trans_mapping->created_by = Auth::user()->id;
    //         $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
    //         $acc_trans_mapping->save();

    //         //If change return then set type as debit

    //         $transactionPayment =  AccountingAccountsTransaction::create([
    //             'amount' => $amount,
    //             'acc_trans_mapping_id' => $acc_trans_mapping->id,
    //             'type' => $aac_tranasction_type,
    //             'sub_type' => $type,
    //             'note' => isset($request->additionalNotes) ?? "",
    //             'operation_date' => now()->format('Y-m-d'),
    //             'created_by' => Auth::user()->id,
    //             'accounting_account_id' => $customer->account->id,

    //         ]);
    //     }

    //     return $transactionPayment;
    // }
    public function addRemainingAmountToCustomerAccount($customerId, $amount, $transaction = null)
    {
        $customer = Contact::find($customerId);

        if ($customer && $customer->account) {
            $ref_number = AccountingUtil::generateReferenceNumber('contact_balance');
            $acc_trans_mapping = new AccountingAccTransMapping;

            $type = $transaction->type ?? '';
            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = 'رصيد متبقي للعميل';
            $acc_trans_mapping->type = $type;
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->operation_date = now()->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();

            AccountingAccountsTransaction::create([
                'amount' => $amount,
                'acc_trans_mapping_id' => $acc_trans_mapping->id,
                'type' => 'credit',
                'sub_type' => $type,
                'note' => 'رصيد متبقي للعميل',
                'operation_date' => now()->format('Y-m-d'),
                'created_by' => Auth::user()->id,
                'accounting_account_id' => $customer->account->id,
            ]);
        }

        return true;
    }

    public function addRemainingAmountToSupplierAccount($supplierId, $amount, $transaction = null)
    {
        $supplier = Contact::find($supplierId);

        if ($supplier && $supplier->account) {
            $ref_number = AccountingUtil::generateReferenceNumber('contact_balance');
            $acc_trans_mapping = new AccountingAccTransMapping;

            $type = $transaction->type ?? '';
            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = 'رصيد متبقي للمورد';
            $acc_trans_mapping->type = $type;
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->operation_date = now()->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();

            // Supplier overpayment typically becomes a debit balance (advance) on supplier account.
            AccountingAccountsTransaction::create([
                'amount' => $amount,
                'acc_trans_mapping_id' => $acc_trans_mapping->id,
                'type' => 'debit',
                'sub_type' => $type,
                'note' => 'رصيد متبقي للمورد',
                'operation_date' => now()->format('Y-m-d'),
                'created_by' => Auth::user()->id,
                'accounting_account_id' => $supplier->account->id,
            ]);
        }

        return true;
    }
}
