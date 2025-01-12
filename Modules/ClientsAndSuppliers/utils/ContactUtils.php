<?php

namespace Modules\ClientsAndSuppliers\utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\TransactionPayments;

class ContactUtils
{

    public function addRemainingAmountToCustomerAccount($customerId, $amount)
    {
        $customer = Contact::find($customerId);
        $transactionPayment=null;
        if ($customer) {

            if (empty($ref_number)) {

                $ref_number = AccountingUtil::generateReferenceNumber('contact_balance');
            }

            $acc_trans_mapping = new AccountingAccTransMapping();


            $acc_trans_mapping->ref_no = $ref_number;
            $acc_trans_mapping->note = 'Remaining paid amount added to customer account';
            $acc_trans_mapping->type = 'contact_balance';
            $acc_trans_mapping->created_by = Auth::user()->id;
            $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
            $acc_trans_mapping->save();

            $transactionPayment =  AccountingAccountsTransaction::create([
                'amount' => $amount,
                'acc_trans_mapping_id'=>$acc_trans_mapping->id,
                'type' => 'credit',
                'sub_type' => 'contact_balance' ??  0,
                'note' => 'Remaining paid amount added to customer account',
                'operation_date' => now()->format('Y-m-d'),
                'created_by' => Auth::user()->id,
                'accounting_account_id' => $customer->account->id,

            ]);
        }

        return $transactionPayment;
    }
}