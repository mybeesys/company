<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\ModifierClass;
use Modules\Product\Models\TreeBuilder;

class ModifierClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::modifier.index' ); 
    }

    public function getModifiers()
    {
        $TreeBuilder = new TreeBuilder();
        $modifiers = ModifierClass::all();
        $tree = $TreeBuilder->buildTree($modifiers, null, 'modifierClass', null);
        return response()->json($tree);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'order' => 'required|numeric',
            'active' => 'nullable|boolean'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $product = ModifierClass::find($validated['id']); 
            $product->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(!isset($validated['id']))
        {
            ModifierClass::create($validated);
        }
        else
        {
            $modifierClass = ModifierClass::find($validated['id']);
            $modifierClass->name_ar  = $validated['name_ar'];
            $modifierClass->name_en  = $validated['name_en'];
            $modifierClass->active   = $validated['active'];
            $modifierClass->order   = $validated['order'];
            $modifierClass->save();
        }
        return response()->json(["message"=>"Done"]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = ModifierClass::find($id);

        if ($item) {
            return response()->json($item);
        }

        return response()->json(['error' => 'Item not found'], 404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
         $product  = ModifierClass::find($id);
         return view('product::product.edit', compact('product'));
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
