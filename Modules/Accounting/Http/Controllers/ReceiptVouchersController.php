<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;

class ReceiptVouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = AccountingAccountsTransaction::where('sub_type', 'receipt_voucher')
            ->orderBy('id')
            ->get();


        if ($request->ajax()) {

            $transactions = AccountingAccountsTransaction::where('sub_type', 'receipt_voucher')
                ->orderBy('id')
                ->get();
            return  AccountingAccountsTransaction::getReceiptsTable($transactions);
        }

        $columns = AccountingAccountsTransaction::getReceiptsColumns();
        $accounts =  AccountingAccount::forDropdown();

        return view('accounting::receipt-vouchers.index', compact('transactions', 'accounts', 'columns'));

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $account_id = $request->input('account_id');
            $note = $request->input('additionalNotes');

            if (!empty($request->input('paid_amount'))) {
                $debit_data = [
                    'amount' => $request->input('paid_amount'),
                    'accounting_account_id' => $account_id,
                    'type' => 'debit',
                    'sub_type' => 'receipt_voucher',
                    'operation_date' => $request->input('paid_on'),
                    'created_by' => Auth::user()->id,
                    'note' => $note,
                ];
                $debit = AccountingAccountsTransaction::query()->create($debit_data);

                $from_account = $request->input('from_account');
                if (!empty($from_account)) {
                    $credit_data = $debit_data;
                    $credit_data['type'] = 'credit';
                    $credit_data['accounting_account_id'] = $from_account;
                    $credit_data['transaction_id'] = $debit->id;

                    $credit = AccountingAccountsTransaction::query()->create($credit_data);

                    $debit->transaction_id = $credit->id;
                    $debit->save();
                }
            }

            DB::commit();
            return redirect()->route('receipt-vouchers')->with('success', __('messages.add_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('receipt-vouchers')->with('error', __('messages.something_went_wrong'));
        }

    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('accounting::edit');
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