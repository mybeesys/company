<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\ProductModifier;

class ProductModifierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::product.index' ); 
    }

    public function getModifiers()
    {
        $TreeBuilder = new TreeBuilder();
        $modifiers = ModifierClass::all();
        $tree = $TreeBuilder->buildTree($modifiers, null, 'modifierClass', null, null, null);
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
            'active' => 'nullable|boolean',
            'method' => 'nullable|string'
        ]);
        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $modifier = Modifier::where([['class_id', '=', $validated['id']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"CHILD_EXIST"]);
            
            $modifierClass = ModifierClass::find($validated['id']); 
            $modifierClass->delete();
            
        }
        else if(!isset($validated['id']))
        {
            $modifierClass = ModifierClass::where('order', $validated['order'])->first();
            if($modifierClass != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $modifierClass = ModifierClass::where('name_ar', $validated['name_ar'])->first();
            if($modifierClass != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $modifierClass = ModifierClass::where('name_en', $validated['name_en'])->first();
            if($modifierClass != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            ModifierClass::create($validated);
        }
        else
        {
            $modifierClass = ModifierClass::where('order', $validated['order'])->where('id', '!=', $validated['id'])->first();
            if($modifierClass != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $modifierClass = ModifierClass::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first();
            if($modifierClass != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $modifierClass = ModifierClass::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first();
            if($modifierClass != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

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
