<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\AttributeClass;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Attribute;

class AttributesClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::attribute.index' ); 
    }

    public function getAttributes()
    {
        $TreeBuilder = new TreeBuilder();
        $attributes = AttributeClass::all();
        $tree = $TreeBuilder->buildTree($attributes, null, 'attributeClass', null, null, null);
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
            $attr = Attribute::where([['class_id', '=', $validated['id']]])->first();
            if($attr != null)
                return response()->json(["message"=>"CHILD_EXIST"]);
            
            $attributeClass = AttributeClass::find($validated['id']); 
            $attributeClass->delete();
            
        }
        else if(!isset($validated['id']))
        {
            $attributeClass = AttributeClass::where('order', $validated['order'])->first();
            if($attributeClass != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $attributeClass = AttributeClass::where('name_ar', $validated['name_ar'])->first();
            if($attributeClass != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $attributeClass = AttributeClass::where('name_en', $validated['name_en'])->first();
            if($attributeClass != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            AttributeClass::create($validated);
        }
        else
        {
            $AttributeClass = AttributeClass::where('order', $validated['order'])->where('id', '!=', $validated['id'])->first();
            if($AttributeClass != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $AttributeClass = AttributeClass::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first();
            if($AttributeClass != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $AttributeClass = AttributeClass::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first();
            if($AttributeClass != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $AttributeClass = AttributeClass::find($validated['id']);
            $AttributeClass->name_ar  = $validated['name_ar'];
            $AttributeClass->name_en  = $validated['name_en'];
            $AttributeClass->active   = $validated['active'];
            $AttributeClass->order   = $validated['order'];
            $AttributeClass->save();
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
         $product  = AttributeClass::find($id);
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
