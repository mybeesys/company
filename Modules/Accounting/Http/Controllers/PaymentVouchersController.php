<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;

class PaymentVouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = AccountingAccountsTransaction::where('sub_type', 'payment_voucher')
            ->orderBy('id')
            ->get();


        if ($request->ajax()) {

            $transactions = AccountingAccountsTransaction::where('sub_type', 'payment_voucher')
                ->orderBy('id')
                ->get();
            return  AccountingAccountsTransaction::getReceiptsTable($transactions);
        }

        $columns = AccountingAccountsTransaction::getReceiptsColumns();
        $accounts =  AccountingAccount::forDropdown();

        return view('accounting::payment-vouchers.index', compact('transactions', 'accounts', 'columns'));
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
        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'min:1'],
            'from_account' => ['required', 'integer', 'min:1', 'different:account_id'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'pament_on' => ['required', 'date'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::beginTransaction();

            $note = $validated['additionalNotes'] ?? null;
            $amount = number_format((float) $validated['paid_amount'], 2, '.', '');

            $credit_data = [
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['account_id'],
                'type' => 'credit',
                'sub_type' => 'payment_voucher',
                'operation_date' => $validated['pament_on'],
                'created_by' => Auth::user()->id,
                'note' => $note,
            ];

            $credit = AccountingAccountsTransaction::query()->create($credit_data);

            $debit_data = $credit_data;
            $debit_data['type'] = 'debit';
            $debit_data['accounting_account_id'] = (int) $validated['from_account'];
            $debit_data['transaction_id'] = $credit->id;

            $debit = AccountingAccountsTransaction::query()->create($debit_data);

            $credit->transaction_id = $debit->id;
            $credit->save();

            DB::commit();

            return redirect()->route('payment-vouchers')->with('success', __('messages.add_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('payment-vouchers')->with('error', __('messages.something_went_wrong'));
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
