<?php

namespace Modules\Purchases\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\AccountingAccount;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Country;
use Modules\General\Models\TransactionPayments;

class SuppliersReceiptsController extends Controller
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
                        $q->whereIn('type', ['purchases']);
                    });
            })
            ->orderBy('id')
            ->get();


        if ($request->ajax()) {

            $transactions = TransactionPayments::with('transaction')
                ->where(function ($q) {
                    $q->where('payment_type', 'debit')
                        ->orWhereHas('transaction', function ($q) {
                            $q->whereIn('type', ['purchases']);
                        });
                })
                ->orderBy('id')
                ->get();
            return  TransactionPayments::getReceiptsTable($transactions);
        }

        $columns = TransactionPayments::getSuppliersReceiptsColumns();

        return view('purchases::receipts.index', compact('transactions', 'columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Contact::where('business_type', 'supplier')->get();
        $accounts =  AccountingAccount::forDropdown();
        $countries = Country::all();
        $supplier=true;


        return view('sales::receipts.create', compact('clients', 'supplier','accounts', 'countries'));
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
        return view('purchases::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('purchases::edit');
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