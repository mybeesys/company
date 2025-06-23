<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Tax;

class TaxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
      

        if ($request->ajax()) {

            $taxes = Tax::all();
            return  Tax::getTaxesTable($taxes);
        }

        $columns = Tax::getsTaxesColumns();
        $taxes = Tax::where('is_tax_group', 0)->get();

        return view('general::tax.index', compact('columns', 'taxes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            DB::beginTransaction();
            if ($request->group_tax_checkbox != 'on') {
                Tax::create([
                    'name' => $request->tax_name . ' (' . $request->tax_amount . '%)',
                    'name_en' => '(' . $request->tax_amount . '%) ' . $request->tax_name_en,
                    'amount' => $request->tax_amount,
                    'for_tax_group' => 0,
                    'is_tax_group' => 0,
                    'created_by' => Auth::user()->id,
                ]);
            } else {
                $sub_tax_ids = $request->input('group_tax');

                $sub_taxes = Tax::whereIn('id', $sub_tax_ids)->get();
                $amount = 0;
                foreach ($sub_taxes as $sub_tax) {
                    $amount += $sub_tax->amount;
                }
                $input['amount'] = $amount;
                $input['is_tax_group'] = 1;

                $tax_rate =    Tax::create([
                    'name' => $request->tax_name . ' (' . $amount . '%)',
                    'name_en' => '(' . $amount . '%) ' . $request->tax_name_en,
                    'amount' => $amount,
                    'for_tax_group' => 0,
                    'is_tax_group' => 1,
                    'created_by' => Auth::user()->id,
                ]);
                $tax_rate->sub_taxes()->sync($sub_tax_ids);
            }

            DB::commit();
            return redirect()->back()->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('general::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('general::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            DB::beginTransaction();
            $tax = Tax::find($request->id);
            if ($request->group_tax_checkbox) {
                $sub_tax_ids = $request->input('group_tax');

                $sub_taxes = Tax::whereIn('id', $sub_tax_ids)->get();
                $amount = 0;
                foreach ($sub_taxes as $sub_tax) {
                    $amount += $sub_tax->amount;
                }

                $tax_rate = Tax::find($request->id);
                $tax_rate->name = preg_replace('/\([^)]*\)\s*/', '', $request->tax_name) . ' (' . $amount . '%)';
                $tax_rate->name_en = '(' . $amount . '%) ' . preg_replace('/\([^)]*\)\s*/', '', $request->tax_name_en);
                $tax_rate->amount = $amount;
                $tax_rate->save();
                $tax_rate->sub_taxes()->sync($sub_tax_ids);
            } else {
                $tax->update([
                    'name' => preg_replace('/\([^)]*\)\s*/', '', $request->tax_name) . ' (' . $request->tax_amount . '%)',
                    'name_en' => '(' . $request->tax_amount . '%) ' . preg_replace('/\([^)]*\)\s*/', '', $request->tax_name_en),
                    'amount' => $request->tax_amount,
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', __('messages.updated_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            $tax = Tax::find($id)->delete();

            DB::commit();
            return redirect()->route('taxes')->with('success', __('messages.deleted_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('taxes')->with('error', __('messages.something_went_wrong'));
        }
    }
}