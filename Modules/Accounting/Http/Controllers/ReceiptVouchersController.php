<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Utils\StandaloneVoucherHelper;

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

            return AccountingAccountsTransaction::getReceiptsTable($transactions, 'receipt_voucher');
        }

        $columns = AccountingAccountsTransaction::getReceiptsColumns();
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();

        return view('accounting::receipt-vouchers.index', compact('transactions', 'accounts', 'columns', 'cost_centers'));

    }

    public function formData(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            return response()->json(
                StandaloneVoucherHelper::receiptFormPayload((int) $validated['id'])
            );
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => __('accounting::lang.voucher_line_not_found'),
            ], 422);
        }
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
            'cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:accounting_cost_centers,id'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            DB::beginTransaction();

            $note = $validated['additionalNotes'] ?? null;
            $amount = number_format((float) $validated['paid_amount'], 2, '.', '');

            $debit_data = [
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['account_id'],
                'type' => 'debit',
                'sub_type' => 'receipt_voucher',
                'operation_date' => $validated['pament_on'],
                'cost_center_id' => $validated['cost_center_id'] ?? null,
                'created_by' => Auth::user()->id,
                'note' => $note,
            ];

            $debit = AccountingAccountsTransaction::query()->create($debit_data);

            $credit_data = $debit_data;
            $credit_data['type'] = 'credit';
            $credit_data['accounting_account_id'] = (int) $validated['from_account'];
            $credit_data['transaction_id'] = $debit->id;

            $credit = AccountingAccountsTransaction::query()->create($credit_data);

            $debit->transaction_id = $credit->id;
            $debit->save();

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
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'min:1'],
            'from_account' => ['required', 'integer', 'min:1', 'different:account_id'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'pament_on' => ['required', 'date'],
            'cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:accounting_cost_centers,id'],
            'additionalNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            [$debit, $credit] = StandaloneVoucherHelper::receiptLines($id);

            DB::beginTransaction();

            $note = $validated['additionalNotes'] ?? null;
            $amount = number_format((float) $validated['paid_amount'], 2, '.', '');
            $date = $validated['pament_on'];
            $costCenterId = $validated['cost_center_id'] ?? null;

            $debit->update([
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['account_id'],
                'operation_date' => $date,
                'cost_center_id' => $costCenterId,
                'note' => $note,
            ]);

            $credit->update([
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['from_account'],
                'operation_date' => $date,
                'cost_center_id' => $costCenterId,
                'note' => $note,
            ]);

            DB::commit();

            return redirect()->route('receipt-vouchers')->with('success', __('messages.updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('receipt-vouchers')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
