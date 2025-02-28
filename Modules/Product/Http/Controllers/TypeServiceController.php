<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\TypesOfService;

class TypeServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */



    public function index(Request $request)
    {
        $typesOfService = TypesOfService::all();


        if ($request->ajax()) {

            return TypesOfService::getTypesOfServiceTable($typesOfService);
        }

        $columns = TypesOfService::getTypesOfServiceColumns();



        return view('product::type-service.index', compact('columns', 'typesOfService'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::type-service.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $input = $request->only([
                'name_ar',
                'name_en',
                'description',
                'location_price_group',
                'packing_charge_type',
                'packing_charge',
            ]);


            TypesOfService::create($input);
            DB::commit();
            return redirect()->route('type-service')->with(200, 'messages.add_successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('type-service')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $service= TypesOfService::find($id);

        return view('product::type-service.edit',compact('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $input = $request->only([
                'name_ar',
                'name_en',
                'description',
                'location_price_group',
                'packing_charge_type',
                'packing_charge',
            ]);


           $service= TypesOfService::find($request->id);
           $service->update($input);
            DB::commit();
            return redirect()->route('type-service')->with(200, 'messages.updated_successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('type-service')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}