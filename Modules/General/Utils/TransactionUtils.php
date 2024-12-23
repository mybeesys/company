<?php

namespace Modules\General\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;

class TransactionUtils
{
    public function createOrUpdatePaymentLines($transaction, $request)
    {

        $accountUtil = new  AccountingUtil();
        //If status is draft don't add payment
        if ($transaction->status == 'draft') {
            return true;
        }

        $c = 0;
        $prefix_type = 'sell_payment';
        if ($transaction->type == 'purchase') {
            $prefix_type = 'purchase_payment';
        }

        $prefix_type = 'sell_payment';
        if ($transaction->type == 'purchase') {
            $prefix_type = 'purchase_payment';
        }

        $date = Carbon::parse($request->pament_on);

        $pament_on = $date->format('Y-m-d H:i:s');

        if ($request->invoice_type == 'cash') {
            $account_id = $request->cash_account;
            $payment_amount = $request->paid_amount;
            $payment_method = 'cash';
        }

        if ($request->invoice_type == 'due') {
            $account_id = $request->account_id;
            $payment_amount = $request->paid_amount;
            $payment_method = 'due';
        }
        $payment_ref_no = $this->generateReferenceNumber($prefix_type);

        $transactionPayment =  TransactionPayments::create([
            'transaction_id' => $transaction->id,
            'payment_type' => $request->invoice_type,
            'amount' => $payment_amount,
            'method' => $payment_method,
            'is_return' => $request->is_return ??  0,
            'note' => $request->additionalNotes,
            'paid_on' => $pament_on,
            'created_by' => Auth::user()->id,
            'payment_for' => $transaction->contact_id,
            'payment_ref_no' => $payment_ref_no,
            'account_id' => $account_id,
        ]);

        $accountUtil->saveAccountTransaction($transaction->type, $transactionPayment, $transaction);

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
}