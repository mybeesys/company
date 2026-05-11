<?php

namespace Modules\Purchases\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingCostCenter;
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
        $query = TransactionPayments::query()
            ->with(['transaction', 'client'])
            ->where(function ($q) {
                $q->where('payment_type', 'debit')
                    ->orWhereHas('transaction', function ($q) {
                        $q->whereIn('type', ['purchases', 'purchase']);
                    });
            })
            ->orderByDesc('id');

        if ($request->ajax()) {
            return TransactionPayments::getReceiptsTable($query);
        }

        $columns = TransactionPayments::getSuppliersReceiptsColumns();

        $hasTransactions = $query->exists();

        return view('purchases::receipts.index', compact('hasTransactions', 'columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $clients = Contact::where('business_type', 'supplier')->get();
        $accounts = AccountingAccount::forDropdown();
        $countries = Country::all();
        $supplier = true;
        $cost_centers = AccountingCostCenter::forDropdown();

        return view('sales::receipts.create', compact('clients', 'cost_centers', 'supplier', 'accounts', 'countries'));
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
