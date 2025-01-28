<?php

namespace Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\utils\ContactUtils;
use Modules\General\Models\Country;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
use Modules\General\Utils\TransactionUtils;

class ReceiptsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = TransactionPayments::with('transaction')
            ->where(function ($q) {
                $q->where('payment_type', 'debit')
                    ->orWhereHas('transaction', function ($q) {
                        $q->whereIn('type', ['sell']);
                    });
            })
            ->orderBy('id')
            ->get();


        if ($request->ajax()) {

            $transactions = TransactionPayments::with('transaction')
                ->where(function ($q) {
                    $q->where('payment_type', 'debit')
                        ->orWhereHas('transaction', function ($q) {
                            $q->whereIn('type', ['sell']);
                        });
                })
                ->orderBy('id')
                ->get();
            return  TransactionPayments::getReceiptsTable($transactions);
        }

        $columns = TransactionPayments::getReceiptsColumns();

        return view('sales::receipts.index', compact('transactions', 'columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Contact::where('business_type', 'customer')->get();
        $accounts =  AccountingAccount::forDropdown();
        $countries = Country::all();
        $supplier = false;



        return view('sales::receipts.create', compact('clients', 'supplier', 'accounts', 'countries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // return $request;
        try {
            DB::beginTransaction();
            if ($request->allocation_option == 'specified_invoices') {
                $transactions = Transaction::whereIn('id', $request->transactions)->get();
                $this->settleTransactions($transactions, $request);
            } else {
                $transactions = Transaction::where('contact_id', $request->client_id)->where('payment_status', '<>', 'paid')->whereIn('type', ['sell'])->get();
                $this->settleTransactions($transactions, $request);
            }

            DB::commit();
            return redirect()->route('receipts')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('receipts')->with('error', __('messages.something_went_wrong'));
        }
    }



    function settleTransactions($transactions, $request)
    {

        $transactionUtil = new TransactionUtils();
        $contactUtils = new ContactUtils();
        $paid_amount = $request->paid_amount;
        foreach ($transactions as $transaction) {
            $paidAmount = $transactionUtil->getTotalPaid($transaction->id);

            $remaining_amount = $transaction->final_total - $paidAmount;

            if ($remaining_amount > 0) {
                $transaction->remaining_amount = number_format($remaining_amount, 2);
            }
        }
        $transactions = $transactions->sortBy('transaction_date');

        $settledTransactions = [];
        foreach ($transactions as $transaction) {
            $remaining_amount = $transaction['remaining_amount'];

            if ($paid_amount == 0) {
                break;
            }

            $remaining_amount = str_replace(',', '', $remaining_amount);
            $paid_amount = str_replace(',', '', $paid_amount);
            // return $transaction;
            $remaining_amount = (float)$remaining_amount;
            $paid_amount = (float)$paid_amount;
            if ($paid_amount >= $remaining_amount) {
                // Pay the full remaining amount of this invoice.
                $paid_amount -= $remaining_amount;
                $request->merge(['paid_amount' => $remaining_amount]);
                // Paid in full
                $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            } else {
                // Partial payment of the bill
                $request->merge(['paid_amount' => $paid_amount]);
                $transactionUtil->createOrUpdatePaymentLines($transaction, $request);
                $transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                $paid_amount = 0;
            }

            if ($paid_amount > 0) {
                $contactUtils->addRemainingAmountToCustomerAccount($request->client_id, $paid_amount);
            }


            $settledTransactions[] = $transaction;
        }




        return $settledTransactions;
    }


    /**
     * Show the specified resource.
     */
    public function getTransactions($clientId)
    {
        $transactionUtil = new TransactionUtils();

        $transactions = Transaction::where('contact_id', $clientId)->where('payment_status', '<>', 'paid')->where('status', 'final')->get();

        $filteredTransactions = [];

        foreach ($transactions as $transaction) {
            $paid_amount = $transactionUtil->getTotalPaid($transaction->id);

            $remaining_amount = $transaction->final_total - $paid_amount;

            if ($remaining_amount > 0) {
                $transaction->remaining_amount = number_format($remaining_amount, 2);
                $filteredTransactions[] = $transaction;
            }
        }

        return $filteredTransactions;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('sales::edit');
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