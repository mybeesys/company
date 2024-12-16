<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\Warehouse;
use Modules\Product\Models\TreeBuilder;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::warehouse.index' ); 
    }
    
    public function getWarehouselist()
    {
        $TreeBuilder = new TreeBuilder();
        $warehouses = Warehouse::all();
        $tree = $TreeBuilder->buildTree($warehouses ,null, 'warehouse', null, null, null);
        return response()->json($tree);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('warehouse::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'order' => 'required|numeric',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);
  
        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $warehouse = Warehouse::find($validated['id']);
            $warehouse->delete();
            return response()->json(["message"=>"Done"]);
        }

        if(!isset($validated['id']))
        {
            $warehouse = Warehouse::where('order', $validated['order'])->first();
            if($warehouse != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $warehouse = Warehouse::where('name_ar', $validated['name_ar'])->first();
            if($warehouse != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $warehouse = Warehouse::where('name_en', $validated['name_en'])->first();
            if($warehouse != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $warehouse = Warehouse::create($validated);
        }
         else
         {
            $warehouse = Warehouse::where('order', $validated['order'])->where('id', '!=', $validated['id'])->first();
            if($warehouse != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $warehouse = Warehouse::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first();
            if($warehouse != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $warehouse = Warehouse::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first();
            if($warehouse != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $warehouse = Warehouse::find($validated['id']);
            $warehouse->name_ar = $validated['name_ar'];
            $warehouse->name_en = $validated['name_en'];
            $warehouse->order = $validated['order'];
            $warehouse->save();
         }

        return response()->json(["message"=>"Done"]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('warehouse::edit');
    }
}
