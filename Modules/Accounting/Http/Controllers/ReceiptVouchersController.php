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
use Modules\Accounting\Exceptions\FiscalPeriodException;
use Modules\Accounting\Services\FiscalPeriod\FiscalPeriodGatekeeper;
use Modules\Accounting\Services\StandaloneVoucherJournalPoster;
use Modules\Accounting\Utils\StandaloneVoucherHelper;
use Mpdf\Mpdf;

class ReceiptVouchersController extends Controller
{
    private function buildViewPayload(int $id): array
    {
        return array_merge(
            StandaloneVoucherHelper::buildVoucherViewPayload($id, 'receipt_voucher'),
            [
                'pdfUrl' => route('receipt-vouchers-export-pdf', ['id' => $id]),
            ]
        );
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $transactions = AccountingAccountsTransaction::standaloneVoucherSubType('receipt_voucher')
            ->orderBy('id')
            ->get();

        if ($request->ajax()) {

            $transactions = AccountingAccountsTransaction::standaloneVoucherSubType('receipt_voucher')
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
        $request->merge([
            'additionalNotes' => trim((string) $request->input('additionalNotes', '')),
        ]);

        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'min:1'],
            'from_account' => ['required', 'integer', 'min:1', 'different:account_id'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'pament_on' => ['required', 'date'],
            'cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:accounting_cost_centers,id'],
            'additionalNotes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            FiscalPeriodGatekeeper::assertPostable($validated['pament_on']);

            DB::beginTransaction();

            $note = $validated['additionalNotes'];
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

            $mapping = StandaloneVoucherJournalPoster::createMapping(
                $validated['pament_on'],
                $note,
                __('menuItemLang.receipt_vouchers')
            );
            $debit_data['acc_trans_mapping_id'] = $mapping->id;

            $debit = AccountingAccountsTransaction::query()->create($debit_data);

            $credit_data = $debit_data;
            $credit_data['type'] = 'credit';
            $credit_data['accounting_account_id'] = (int) $validated['from_account'];
            $credit_data['transaction_id'] = $debit->id;

            $credit = AccountingAccountsTransaction::query()->create($credit_data);

            $debit->transaction_id = $credit->id;
            $debit->save();

            StandaloneVoucherJournalPoster::linkLines($debit, $credit, $mapping);

            DB::commit();

            return redirect()->route('receipt-vouchers')->with('success', __('messages.add_successfully'));
        } catch (FiscalPeriodException $e) {
            DB::rollBack();

            return redirect()->route('receipt-vouchers')->with('error', $e->getMessage());
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
        try {
            $payload = $this->buildViewPayload((int) $id);
        } catch (ModelNotFoundException $e) {
            return redirect()->route('receipt-vouchers')->with('error', __('accounting::lang.voucher_line_not_found'));
        }

        return view('accounting::vouchers.show', array_merge($payload, [
            'backUrl' => route('receipt-vouchers'),
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
            return redirect()->route('receipt-vouchers')->with('error', __('accounting::lang.voucher_line_not_found'));
        }
        $html = view('accounting::vouchers.print', array_merge($payload, ['forPrint' => true]))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 11,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);
        $filename = 'receipt-voucher-'.$id.'.pdf';

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
        $request->merge([
            'additionalNotes' => trim((string) $request->input('additionalNotes', '')),
        ]);

        $validated = $request->validate([
            'account_id' => ['required', 'integer', 'min:1'],
            'from_account' => ['required', 'integer', 'min:1', 'different:account_id'],
            'paid_amount' => ['required', 'numeric', 'gt:0'],
            'pament_on' => ['required', 'date'],
            'cost_center_id' => ['nullable', 'integer', 'min:1', 'exists:accounting_cost_centers,id'],
            'additionalNotes' => ['required', 'string', 'max:2000'],
        ]);

        try {
            FiscalPeriodGatekeeper::assertPostable($validated['pament_on']);

            [$debit, $credit] = StandaloneVoucherHelper::receiptLines($id);

            DB::beginTransaction();

            $note = $validated['additionalNotes'];
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

            StandaloneVoucherJournalPoster::syncMapping(
                $debit->fresh(),
                $credit->fresh(),
                $date,
                $note,
                __('menuItemLang.receipt_vouchers')
            );

            DB::commit();

            return redirect()->route('receipt-vouchers')->with('success', __('messages.updated_successfully'));
        } catch (FiscalPeriodException $e) {
            DB::rollBack();

            return redirect()->route('receipt-vouchers')->with('error', $e->getMessage());
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
        try {
            [$debit, $credit] = StandaloneVoucherHelper::receiptLines((int) $id);
            DB::beginTransaction();
            StandaloneVoucherJournalPoster::deleteMappingForLines($debit, $credit);
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
