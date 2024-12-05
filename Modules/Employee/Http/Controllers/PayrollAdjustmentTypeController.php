<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\PayrollAdjustmentType;

class PayrollAdjustmentTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('employee::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('employee::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_lang' => 'required|in:name_en,name',
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:allowance,deduction'
        ]);
        $type = $request->type ?? 'allowance';
        $allowanceType = PayrollAdjustmentType::create([
            $request->name_lang => $request->name,
            'type' => $type
        ]);

        return response()->json([
            'id' => $allowanceType->id,
            'message' => __('employee::responses.created_successfully', ['name' => __("employee::fields.new_{$type}_type")])
        ]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('employee::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('employee::edit');
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
