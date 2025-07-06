<?php

namespace Modules\General\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingAccTransMapping;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Setting;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\Product\Models\Product;

class TransactionUtils
{
    // public function createOrUpdatePaymentLines($transaction, $request)
    // {

    //     $accountUtil = new  AccountingUtil();
    //     //If status is draft don't add payment
    //     if ($transaction->status == 'draft') {
    //         return true;
    //     }

    //     $prefix_type = 'sell_payment';
    //     if ($transaction->type == 'purchase') {
    //         $prefix_type = 'purchase_payment';
    //     }

    //     // $prefix_type = 'sell_payment';
    //     // if ($transaction->type == 'purchase') {
    //     //     $prefix_type = 'purchase_payment';
    //     // }

    //     $date = Carbon::parse($request->pament_on);

    //     $pament_on = $date->format('Y-m-d H:i:s');

    //     if ($transaction->invoice_type == 'cash') {
    //         $account_id = $request->cash_account;
    //         $payment_method = 'cash';
    //         $type ='sell_cash';
    //     }
    //     if ($transaction->invoice_type == 'due') {
    //         $account_id = $request->account_id;
    //         $payment_method = 'due';
    //         $type ='sales_revenue';

    //     }
    //     if(isset($request->payment_type) && $request->payment_type =='receipts'){
    //         $account_id = $request->account_id;

    //     }

    //         $payment_ref_no = $this->generateReferenceNumber($prefix_type);

    //     $transactionPayment =  TransactionPayments::create([
    //         'transaction_id' => $transaction->id,
    //         'payment_type' => $transaction->invoice_type,
    //         'amount' => $request->paid_amount,
    //         'method' => $payment_method,
    //         'is_return' => $request->is_return ??  0,
    //         'note' => $request->additionalNotes,
    //         'paid_on' => $pament_on,
    //         'created_by' => Auth::user()->id,
    //         'payment_for' => $transaction->contact_id,
    //         'payment_ref_no' => $payment_ref_no,
    //         'account_id' => $account_id,
    //     ]);

    //     $accountUtil->saveAccountTransaction($transaction->type, $transactionPayment, $transaction);

    //     return true;
    // }

    public function createOrUpdatePaymentLines($transaction, $request)
    {
        $accountUtil = new AccountingUtil();

        if ($transaction->status == 'draft') {
            return true;
        }

        $prefix_type = $transaction->type == 'purchase' ? 'purchase_payment' : 'sell_payment';
        $date = Carbon::parse($request->payment_on);
        $payment_on = $date->format('Y-m-d H:i:s');
        $due_account_id = '';
        $cash_account_id = '';
        if ($transaction->invoice_type == 'cash') {
            $account_id = $request->cash_account;
            $cash_account_id = $request->cash_account;
            $payment_method = 'cash';
            $type = 'sell_cash';
        } elseif ($transaction->invoice_type == 'due') {
            $account_id = $request->account_id;
            $due_account_id = $request->account_id;
            $payment_method = 'due';
            $type = 'sales_revenue';
        }

        $payment_ref_no = $this->generateReferenceNumber($prefix_type);
        $payment_method_id = null;

        if (!$request->has('payment_method_id')) {
            $payment_method_id = $request->payment_method_id;
        }

        $transactionPayment = TransactionPayments::create([
            'transaction_id' => $transaction->id,
            'payment_type' => $transaction->invoice_type,
            'amount' => $request->paid_amount,
            'method' => $payment_method,
            'payment_method_id' => $payment_method_id,
            'is_return' => $request->is_return ?? 0,
            'note' => $request->additionalNotes,
            'paid_on' => $payment_on,
            'created_by' => Auth::user()->id,
            'payment_for' => $transaction->contact_id,
            'payment_ref_no' => $payment_ref_no,
            'account_id' => $account_id,
        ]);

        $accountUtil->accounts_route($transactionPayment, $transaction, $cash_account_id, $due_account_id, $request);
        // $accountUtil->saveAccountTransaction($transaction->type, $transactionPayment, $transaction);

        // $inventoryMethod = Setting::where('key', 'inventory_tracking_policy')->first()->value;
        // if ($inventoryMethod == 'perpetual' && $transaction->type == 'sell') {
        //     $this->recordCOGS($transaction);
        // }

        return true;
    }

    protected function recordCOGS($transaction)
    {
        $cogsAmount = 0;
        foreach ($transaction->sell_lines as $line) {
            $product = Product::find($line->product_id);
            $cogsAmount += $product->cost_price * $line->quantity;
        }

        //  COGS (521)
        $cogsAccount = AccountingAccount::where('gl_code', '521')->first();

        if ($cogsAccount) {
            AccountingAccountsTransaction::create([
                'accounting_account_id' => $cogsAccount->id,
                'amount' => $cogsAmount,
                'type' => 'debit',
                'transaction_id' => $transaction->id,
                'operation_date' => $transaction->transaction_date
            ]);
        }
    }

    public function addPaymentLines_journalEntry($transaction, $request)
    {
        $accountUtil = new AccountingUtil();

        if ($transaction->status == 'draft') {
            return true;
        }

        $prefix_type = $transaction->type == 'purchase' ? 'purchase_payment' : 'sell_payment';
        $date = Carbon::parse($request->payment_on);
        $payment_on = $date->format('Y-m-d H:i:s');
        $account_id = $request->account_id;
        // $payment_method ='';
        if ($transaction->invoice_type == 'cash') {
            $payment_method = 'cash';
        } elseif ($transaction->invoice_type == 'due') {
            $payment_method = 'due';
        }

        $payment_ref_no = $this->generateReferenceNumber($prefix_type);

        $transactionPayment = TransactionPayments::create([
            'transaction_id' => $transaction->id,
            'payment_type' => $transaction->invoice_type,
            'amount' => $request->paid_amount,
            'method' => $payment_method,
            'is_return' => $request->is_return ?? 0,
            'note' => $request->additionalNotes,
            'paid_on' => $payment_on,
            'created_by' => Auth::user()->id,
            'payment_for' => $transaction->contact_id,
            'payment_ref_no' => $payment_ref_no,
            'account_id' => $account_id,
        ]);

        $acc_trans_mapping = new AccountingAccTransMapping();

        $ref_number = $this->generateReferenceNumber('journal_entry');
        $acc_trans_mapping->ref_no = $ref_number;
        $acc_trans_mapping->note = '';
        $acc_trans_mapping->type = 'journal_entry';
        $acc_trans_mapping->created_by = Auth::user()->id;
        $acc_trans_mapping->operation_date = Carbon::parse(now())->format('Y-m-d H:i:s');
        $acc_trans_mapping->save();
        $acc_trans_mapping_id = $acc_trans_mapping->id;

        if ($transaction->type == 'sell') {
            $transaction->type = 'receipt_voucher';

            $client = Contact::find($transactionPayment->payment_for);
            if ($client) {
                $transactionPayment->account_id = $client->account_id;
                $transactionPayment->amount = $transaction->final_total;
                $accountUtil->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
            }
            $transactionPayment->account_id = $account_id;
            $transactionPayment->amount = $request->paid_amount;
            $accountUtil->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
        } elseif ($transaction->type == 'purchases') {

            $transaction->type = 'payment_voucher';
            $client = Contact::find($transactionPayment->payment_for);
            if ($client) {
                $transactionPayment->account_id = $client->account_id;
                $transactionPayment->amount = $transaction->final_total;
                $accountUtil->saveAccountRouteTransaction('debit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
            }
            $transactionPayment->account_id = $account_id;
            $transactionPayment->amount = $request->paid_amount;
            $accountUtil->saveAccountRouteTransaction('credit', $transactionPayment, $transaction, $acc_trans_mapping_id, $request);
        }

        return true;
    }

    public  function updatePaymentStatus($transaction_id, $final_amount = null)
    {
        $status = $this->calculatePaymentStatus($transaction_id, $final_amount);

        $transaction = Transaction::find($transaction_id);
        $transaction->payment_status = $status;
        $transaction->save();

        return $status;
    }
    public  function calculatePaymentStatus($transaction_id, $final_amount = null)
    {
        $total_paid = $this->getTotalPaid($transaction_id);
        if (is_null($final_amount)) {
            $final_amount = Transaction::find($transaction_id)->final_total;
        }

        $status = 'due';
        if ((int)$final_amount <= ($total_paid ?? 0)) {
            $status = 'paid';
        } elseif ($total_paid > 0 && $final_amount > $total_paid) {
            $status = 'partial';
        }

        return $status;
    }
    public function getTotalPaid($transaction_id)
    {
        $total_paid = 0;
        $total_paid = TransactionPayments::where('transaction_id', $transaction_id)
            ->select(DB::raw('SUM(IF( is_return = 0, amount, amount*-1))as total_paid'))
            ->first()
            ->total_paid;

        return $total_paid;
    }

    public  function generateReferenceNumber($type)
    {
        $currentYear = date('Y');

        $transactionPayments = TransactionPayments::whereYear('created_at', $currentYear)
            ->latest()
            ->first();

        $prefix_type = 'SP-';
        if ($type == 'purchase') {
            $prefix_type = 'PP-';
        }

        if ($transactionPayments) {
            $last_ref_no = $transactionPayments->payment_ref_no;

            list(, $yearAndNumber) = explode('-', $last_ref_no);
            list($year, $number) = explode('/', $yearAndNumber);

            if ($year == $currentYear) {
                $newNumber = str_pad($number + 1, 4, '0', STR_PAD_LEFT);
                $new_ref_no = $prefix_type . $currentYear . '/' . $newNumber;
            } else {
                $new_ref_no = $prefix_type . $currentYear . '/0001';
            }
        } else {
            $new_ref_no = $prefix_type . $currentYear . '/0001';
        }

        return $new_ref_no;
    }

    public function contactTotalOutstanding($transaction)
    {
        if ($transaction->contact_id) {
            $customerId = $transaction->contact_id;
            $transactionType = $transaction->type;
            $totalDue = Transaction::where('contact_id', $customerId)
                ->where('type', $transactionType)
                ->where('payment_status', 'due')
                ->sum('final_total');


            $totalPartial = Transaction::where('contact_id', $customerId)
                ->where('type', $transactionType)
                ->where('payment_status', 'partial')
                ->get()
                ->sum(function ($transaction) {
                    $paidAmount = $transaction->payment()->sum('amount');
                    return $transaction->final_total - $paidAmount;
                });

            $totalOutstanding = $totalDue + $totalPartial;

            return $totalOutstanding;
            return response()->json([
                'customer_id' => $customerId,
                'transaction_type' => $transactionType,
                'total_due' => $totalDue,
                'total_partial_due' => $totalPartial,
                'total_outstanding' => $totalOutstanding
            ]);
        }
        return false;
    }
}
