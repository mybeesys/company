<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Unit;


class UnitController extends Controller
{
    public function getUnitsTree()
    {
        $ingredients = Unit::all();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($ingredients ,null, 'Unit', null, null, null);
        return response()->json($tree);
    }

    public function index()
    {
        return view('product::unit.index' ); 
    }

    public function edit($id)
    {
        $unit  = Unit::find($id);
        return view('product::unit.edit', compact('unit'));
    }

    public function create()
    {
        $ingredient  = new Unit();
    
        return view('product::unit.create', compact('unit'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $serviceFee = Unit::find($validated['id']);
            $serviceFee->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $unit = Unit::where('name_ar', $validated['name_ar'])->first();
            if($unit != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $unit = Unit::where('name_en', $validated['name_en'])->first();
            if($unit != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            
                Unit::create($validated);
            
        } else {
            $unit = Unit::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($unit != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $unit = Unit::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($unit != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $Ingredient = Unit::find($validated['id']);
            $Ingredient->name_ar = $validated['name_ar'];
            $Ingredient->name_en = $validated['name_en'];
            $Ingredient->save();
        }
        return response()->json(["message" => "Done"]);
    }

}