<?php

namespace Modules\Franchise\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

use Carbon\Carbon;
use Modules\Franchise\Models\FranchiseContract;

class FranchiseContractController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'franchise_id' => 'required',
            'contract_duration' => 'required|integer',
            'start_date' => 'required|date',
            'reality_fees' => 'required|numeric',
            'contract_file' => 'nullable|mimes:pdf,jpg,png|max:10240',
        ]);
        $exists = FranchiseContract::where('franchise_id', $request->franchise_id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'لا يمكن إضافة عقد جديد وهناك عقد نشط حالياً'], 422);
        }
        $data = $request->only([
            'franchise_id',
            'contract_duration',
            'start_date',
            'reality_fees',
            'unite_no',
            'notes'
        ]);

        $data['end_date'] = \Carbon\Carbon::parse($request->start_date)
            ->addMonths((int) $request->contract_duration);

        if ($request->hasFile('contract_file')) {
            $file = $request->file('contract_file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $data['contract_file'] = $file->storeAs('franchise_contracts', $fileName, 'public');
        }

        FranchiseContract::create($data);

        return response()->json(['message' => __('franchise::lang.added_successfully')]);
        return response()->json(['success' => true, 'message' => __('franchise::lang.success_msg')]);
    }
    public function destroy($id)
    {
        FranchiseContract::findOrFail($id)->delete();
        return response()->json(['message' => __('franchise::lang.deleted_successfully')]);
    }
}