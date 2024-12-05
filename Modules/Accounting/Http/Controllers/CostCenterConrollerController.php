<?php

namespace Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Exceptions\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\classes\CostCenterExport;
use Modules\Accounting\classes\TransactionsCostCenterExport;
use Modules\Accounting\Models\AccountingCostCenter;
use Modules\Accounting\Utils\CostCenterUtil;
use Modules\Product\Models\Product;
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

    /**
     * Show the form for editing the specified resource.
     */
    public function transactions($id)
    {
        $costCenter = AccountingCostCenter::with('transactions')->find($id);
        $costCenters = AccountingCostCenter::all();

        $previous = AccountingCostCenter::where('id', '<', $id)->orderBy('id', 'desc')->first();

        $next = AccountingCostCenter::where('id', '>', $id)->orderBy('id', 'asc')->first();

        return view('accounting::costCenter.transactions', compact('costCenters', 'costCenter', 'previous', 'next'));
    }

    public function transactionsPrint($id)
    {
        $costCenter = AccountingCostCenter::with('transactions')->find($id);


        return view('accounting::costCenter.transactions_print', compact('costCenter'));
    }

    public function exportTransactionsPDF($id)
    {
        $costCenter = AccountingCostCenter::with('transactions')->find($id);

        $html = view('accounting::costCenter.transactions_print', compact('costCenter'))->render();


        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font' => 'DejaVuSans',
            'default_font_size' => 12,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ]);

        $mpdf->WriteHTML($html);
        $filename = 'cost-centers-' . str_replace(['/', '\\'], ' - ', $costCenter->account_center_number) . '.pdf';

        return $mpdf->Output($filename, 'D');
    }

    public function exportTransactionsExcel($id)
    {
        $costCenter = AccountingCostCenter::with('transactions')->find($id);

        $filename = 'cost-centers-' . str_replace(['/', '\\'], ' - ', $costCenter->account_center_number) . '.xlsx';

        return Excel::download(new TransactionsCostCenterExport($costCenter), $filename);
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