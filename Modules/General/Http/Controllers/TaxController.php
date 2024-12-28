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

            $teaxes = Tax::all();
            return  Tax::getTaxesTable($teaxes);
        }

        $columns = Tax::getsTaxesColumns();
        return view('general::tax.index', compact('columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('general::invoice-setting.setting');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            DB::beginTransaction();
            Tax::create([
                'name' => $request->tax_name,
                'amount' => $request->tax_amount,
                'for_tax_group' => 0,
                'is_tax_group' => 0,
                'created_by'=>Auth::user()->id,
            ]);

            DB::commit();
            return redirect()->route('taxes')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('taxes')->with('error', __('messages.something_went_wrong'));
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
           $tax->update([
                'name' => $request->tax_name,
                'amount' => $request->tax_amount,
            ]);

            DB::commit();
            return redirect()->route('taxes')->with('success', __('messages.updated_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('taxes')->with('error', __('messages.something_went_wrong'));
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