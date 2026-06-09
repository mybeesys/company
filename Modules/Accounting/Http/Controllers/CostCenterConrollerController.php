<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Exceptions\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\CostCenterExport;
use Modules\Accounting\classes\TransactionsCostCenterExport;
use Modules\Accounting\Models\AccountingAccountsTransaction;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Utils\CostCenterUtil;
use Mpdf\Mpdf;

class CostCenterConrollerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $costCenters = AccountingCostCenter::where('parent_id', 'null')->with('chiledCostCenter')->get();
        $includeInactive = $request->includeInactive == 0 ? 1 : 0;

        return view('accounting::costCenter.index', compact('costCenters', 'includeInactive'));
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

        // return $request;
        $next_account_center_number = CostCenterUtil::next_account_center_number($request->parent_account_id);
        try {
            DB::beginTransaction();
            AccountingCostCenter::create([
                'name_ar' => $request->name_ar,
                'name_en' => $request->name_en,
                'account_center_number' => $next_account_center_number,
                'parent_id' => $request->parent_account_id,
                'is_main' => $request->has('is_main') ? 1 : 0,

            ]);

            DB::commit();

            return redirect()->route('cost-center-index')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->route('cost-center-index')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function print()
    {
        $CostCenter = AccountingCostCenter::all();

        return view('accounting::costCenter.print', compact('CostCenter'));
    }

    public function exportPDF()
    {
        $CostCenter = AccountingCostCenter::all();

        $html = view('accounting::costCenter.print', compact('CostCenter'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('cost-centers.pdf', 'D');
    }

    public function exportExcel()
    {
        $CostCenters = AccountingCostCenter::all();

        return Excel::download(new CostCenterExport($CostCenters), 'cost-centers.xlsx');
    }

    /** @return array{0: string, 1: string} */
    protected function costCenterDateRange(Request $request): array
    {
        $start = $request->get('start_date', now()->startOfYear()->format('Y-m-d'));
        $end = $request->get('end_date', now()->addDay()->format('Y-m-d'));

        return [$start, $end];
    }

    protected function buildCostCenterTransactionsQuery(int $costCenterId, Request $request): \Illuminate\Database\Eloquent\Builder
    {
        [$start, $end] = $this->costCenterDateRange($request);

        return AccountingAccountsTransaction::with([
            'accTransMapping',
            'createdBy',
            'transaction',
            'account',
            'transactionPayments',
            'costCenter',
        ])
            ->where('cost_center_id', $costCenterId)
            ->whereBetween('operation_date', [$start, $end])
            ->when($request->filled('ref_no'), function ($query) use ($request) {
                $refNo = trim((string) $request->ref_no);

                return $query->where(function ($subQuery) use ($refNo) {
                    $subQuery->whereHas('accTransMapping', function ($q) use ($refNo) {
                        $q->where('ref_no', 'like', '%'.$refNo.'%');
                    })->orWhereHas('transaction', function ($q) use ($refNo) {
                        $q->where('ref_no', 'like', '%'.$refNo.'%');
                    })->orWhereHas('transactionPayments', function ($q) use ($refNo) {
                        $q->where('payment_ref_no', 'like', '%'.$refNo.'%');
                    });
                });
            })
            ->orderBy('operation_date')
            ->orderBy('id');
    }

    /** @return array{0: ?AccountingCostCenter, 1: ?AccountingCostCenter} */
    protected function leafCostCenterNeighbors(int $id): array
    {
        $leafIds = AccountingCostCenter::leafLevel()
            ->orderBy('account_center_number')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $index = array_search($id, $leafIds, true);
        if ($index === false) {
            return [null, null];
        }

        $previous = isset($leafIds[$index - 1])
            ? AccountingCostCenter::find($leafIds[$index - 1])
            : null;
        $next = isset($leafIds[$index + 1])
            ? AccountingCostCenter::find($leafIds[$index + 1])
            : null;

        return [$previous, $next];
    }

    /**
     * @return array{transactions: \Illuminate\Support\Collection, totalDebit: float, totalCredit: float, start_date: string, end_date: string, isLeaf: bool}
     */
    protected function costCenterTransactionsDataset(Request $request, AccountingCostCenter $costCenter): array
    {
        [$startDate, $endDate] = $this->costCenterDateRange($request);
        $isLeaf = $costCenter->isLeaf();

        if (! $isLeaf) {
            return [
                'transactions' => collect(),
                'totalDebit' => 0.0,
                'totalCredit' => 0.0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'isLeaf' => false,
            ];
        }

        $transactions = $this->buildCostCenterTransactionsQuery((int) $costCenter->id, $request)->get();

        return [
            'transactions' => $transactions,
            'totalDebit' => (float) $transactions->where('type', 'debit')->sum('amount'),
            'totalCredit' => (float) $transactions->where('type', 'credit')->sum('amount'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'isLeaf' => true,
        ];
    }

    /** @return array<string, mixed> */
    protected function costCenterExportQueryParams(Request $request, string $startDate, string $endDate): array
    {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
        if ($request->filled('ref_no')) {
            $params['ref_no'] = $request->ref_no;
        }

        return $params;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function transactions(Request $request, $id)
    {
        $costCenter = AccountingCostCenter::findOrFail($id);
        $costCenters = AccountingCostCenter::leafLevel()
            ->orderBy('account_center_number')
            ->orderBy('id')
            ->get();

        [$previous, $next] = $this->leafCostCenterNeighbors((int) $id);

        $dataset = $this->costCenterTransactionsDataset($request, $costCenter);
        $exportQuery = $this->costCenterExportQueryParams($request, $dataset['start_date'], $dataset['end_date']);

        return view('accounting::costCenter.transactions', array_merge(
            compact('costCenters', 'costCenter', 'previous', 'next', 'exportQuery'),
            $dataset
        ));
    }

    public function transactionsPrint(Request $request, $id)
    {
        $costCenter = AccountingCostCenter::findOrFail($id);
        if (! $costCenter->isLeaf()) {
            return redirect()
                ->route('cost-center-transactions', $costCenter->id)
                ->with('error', __('accounting::lang.cost_center_transactions_leaf_only'));
        }

        $dataset = $this->costCenterTransactionsDataset($request, $costCenter);
        $exportQuery = $this->costCenterExportQueryParams($request, $dataset['start_date'], $dataset['end_date']);

        return view('accounting::costCenter.transactions_print', array_merge(compact('costCenter', 'exportQuery'), $dataset));
    }

    public function exportTransactionsPDF(Request $request, $id)
    {
        $costCenter = AccountingCostCenter::findOrFail($id);
        if (! $costCenter->isLeaf()) {
            return redirect()
                ->route('cost-center-transactions', $costCenter->id)
                ->with('error', __('accounting::lang.cost_center_transactions_leaf_only'));
        }

        $dataset = $this->costCenterTransactionsDataset($request, $costCenter);
        $exportQuery = $this->costCenterExportQueryParams($request, $dataset['start_date'], $dataset['end_date']);

        $html = view('accounting::costCenter.transactions_print', array_merge(compact('costCenter', 'exportQuery'), $dataset))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);
        $filename = 'cost-centers-'.str_replace(['/', '\\'], ' - ', $costCenter->account_center_number).'.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function exportTransactionsExcel(Request $request, $id)
    {
        $costCenter = AccountingCostCenter::findOrFail($id);
        if (! $costCenter->isLeaf()) {
            return redirect()
                ->route('cost-center-transactions', $costCenter->id)
                ->with('error', __('accounting::lang.cost_center_transactions_leaf_only'));
        }

        $dataset = $this->costCenterTransactionsDataset($request, $costCenter);

        $filename = 'cost-centers-'.str_replace(['/', '\\'], ' - ', $costCenter->account_center_number).'.xlsx';

        return Excel::download(
            new TransactionsCostCenterExport($costCenter, $dataset['transactions'], $dataset['totalDebit'], $dataset['totalCredit']),
            $filename
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        try {
            DB::beginTransaction();
            $costCenter = AccountingCostCenter::find($request->costCenter_id);
            $costCenter->update([
                'name_ar' => $request->name_ar,
                'name_en' => $request->name_en,
                'is_main' => $request->has('is_main') ? 1 : 0,

            ]);

            DB::commit();

            return redirect()->route('cost-center-index')->with('success', __('messages.updated_successfully'));
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()->route('cost-center-index')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function changeStatus(Request $request)
    {
        try {
            $costCenter = AccountingCostCenter::find($request->cost_center_id);

            $costCenter->active = $costCenter->active == 1 ? 0 : 1;
            $costCenter->save();

            return redirect()->route('cost-center-index')->with('success', __('messages.updated_successfully'));
        } catch (Exception $e) {
            return redirect()->route('cost-center-index')->with('error', __('messages.something_went_wrong'));
        }
    }
}
