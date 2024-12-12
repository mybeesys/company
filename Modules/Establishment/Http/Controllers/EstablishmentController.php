<?php

namespace Modules\Establishment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Establishment\Classes\EstablishmentTable;
use Modules\Establishment\Models\Establishment;

class EstablishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $establishments = Establishment::with('permissions:id,name')->
            select('id', 'name', 'address', 'city', 'region', 'contact_details', 'is_active');
        if ($request->ajax()) {

            return EstablishmentTable::getEstablishmentTable($establishments);
        }
        $establishments = $establishments->get();
        $columns = EstablishmentTable::getEstablishmentColumns();

        return view('establishment::establishment.index', compact('columns'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('establishment::create');
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
        return view('establishment::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('establishment::edit');
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
