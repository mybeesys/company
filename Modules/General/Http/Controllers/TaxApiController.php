<?php

namespace Modules\General\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\General\Models\Tax;
use Modules\General\Transformers\TaxResource;

class TaxApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function taxes()
    {
        $taxes = Tax::all();
        return TaxResource::collection($taxes);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('general::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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