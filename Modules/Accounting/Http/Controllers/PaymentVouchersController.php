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
use Mpdf\Mpdf;

class PaymentVouchersController extends Controller
{
    private function buildViewPayload(int $id): array
    {
        [$debit, $credit] = StandaloneVoucherHelper::paymentLines($id);
        $debit->loadMissing(['account', 'createdBy', 'costCenter']);
        $credit->loadMissing(['account']);

        $costCenterLabel = $debit->costCenter
            ? ($debit->costCenter->account_center_number.' - '.(app()->getLocale() === 'ar' ? $debit->costCenter->name_ar : $debit->costCenter->name_en))
            : '--';
        $createdByLabel = $debit->createdBy->name ?? '--';
        $debitAccountLabel = $debit->account->gl_code.' - '.(app()->getLocale() === 'ar' ? $debit->account->name_ar : $debit->account->name_en);
        $creditAccountLabel = $credit->account->gl_code.' - '.(app()->getLocale() === 'ar' ? $credit->account->name_ar : $credit->account->name_en);

        return [
            'pageTitle' => __('menuItemLang.payment_vouchers'),
            'date' => $debit->operation_date,
            'amount' => $debit->amount,
            'note' => $debit->note,
            'debitAccountLabel' => $debitAccountLabel,
            'creditAccountLabel' => $creditAccountLabel,
            'debitHint' => __('accounting::lang.voucher_payment_debit_hint'),
            'creditHint' => __('accounting::lang.voucher_payment_credit_hint'),
            'costCenterLabel' => $costCenterLabel,
            'createdByLabel' => $createdByLabel,
            'pdfUrl' => route('payment-vouchers-export-pdf', ['id' => $id]),
        ];
    }

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

            return AccountingAccountsTransaction::getReceiptsTable($transactions, 'payment_voucher');
        }

        $columns = AccountingAccountsTransaction::getReceiptsColumns();
        $accounts = AccountingAccount::forDropdown();
        $cost_centers = AccountingCostCenter::forDropdown();

        return view('accounting::payment-vouchers.index', compact('transactions', 'accounts', 'columns', 'cost_centers'));
    }

    public function formData(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            return response()->json(
                StandaloneVoucherHelper::paymentFormPayload((int) $validated['id'])
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

            $credit_data = [
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['account_id'],
                'type' => 'credit',
                'sub_type' => 'payment_voucher',
                'operation_date' => $validated['pament_on'],
                'cost_center_id' => $validated['cost_center_id'] ?? null,
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
        try {
            $payload = $this->buildViewPayload((int) $id);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('payment-vouchers')->with('error', __('accounting::lang.voucher_line_not_found'));
        }

        return view('accounting::vouchers.show', array_merge($payload, [
            'backUrl' => route('payment-vouchers'),
        ]));
    }

    public function modal(int $id)
    {
        try {
            $payload = $this->buildViewPayload($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => __('accounting::lang.voucher_line_not_found')], 422);
        }

        return response()->json([
            'title' => $payload['pageTitle'],
            'html' => view('accounting::vouchers.partials.show_content', $payload)->render(),
        ]);
    }

    public function exportPDF($id)
    {
        try {
            $payload = $this->buildViewPayload((int) $id);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('payment-vouchers')->with('error', __('accounting::lang.voucher_line_not_found'));
        }
        $html = view('accounting::vouchers.print', $payload)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);
        $filename = 'payment-voucher-'.$id.'.pdf';

        return $mpdf->Output($filename, 'D');
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
            [$debit, $credit] = StandaloneVoucherHelper::paymentLines($id);

            DB::beginTransaction();

            $note = $validated['additionalNotes'] ?? null;
            $amount = number_format((float) $validated['paid_amount'], 2, '.', '');
            $date = $validated['pament_on'];
            $costCenterId = $validated['cost_center_id'] ?? null;

            $debit->update([
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['from_account'],
                'operation_date' => $date,
                'cost_center_id' => $costCenterId,
                'note' => $note,
            ]);

            $credit->update([
                'amount' => $amount,
                'accounting_account_id' => (int) $validated['account_id'],
                'operation_date' => $date,
                'cost_center_id' => $costCenterId,
                'note' => $note,
            ]);

            DB::commit();

            return redirect()->route('payment-vouchers')->with('success', __('messages.updated_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->route('payment-vouchers')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            [$debit, $credit] = StandaloneVoucherHelper::paymentLines((int) $id);
            DB::beginTransaction();
            $debit->delete();
            $credit->delete();
            DB::commit();
            return response()->json(['status' => true]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => __('accounting::lang.voucher_line_not_found')], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => __('messages.something_went_wrong')], 500);
        }
    }
}
