<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\General\Models\Transaction;
use Modules\General\Utils\TransactionUtils;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('general::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('general::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {

        $transaction = Transaction::find($id);
        return view('general::transactions.show',compact('transaction'));
    }

    public function showPayments($id)
    {

        $transactionUtil =new TransactionUtils();
        $transaction = Transaction::find($id);
        $accounts =  AccountingAccount::forDropdown();
        $paid_amount = $transactionUtil->getTotalPaid($id);
        $amount = $transaction->final_total - $paid_amount;
        if ($amount < 0) {
            $amount = 0;
        }

        return view('general::transactions.show-payments',compact('transaction','accounts','amount'));
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function addPayment(Request $request)
    {


        $transactionUtil= new TransactionUtils();

        $transaction = Transaction::find($request->id);
        if ($request->paid_amount) {
            $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
        }

        $payment_status = $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);


        return redirect()->route('invoices')->with('success', __('messages.add_successfully'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}