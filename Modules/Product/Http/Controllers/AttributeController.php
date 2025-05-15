<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Product_Attribute;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::attribute.index');
    }

    public function getProductMatrix($id)
    {
        $product_att = Product_Attribute::where('product_id', $id)->get();

        foreach ($product_att as $att) {
            $att->load(['attribute1', 'attribute2']);

            /* 
             if(isset($att->attribute1))
             $att->attribute1->load('attributeClass');
                  
            if(isset($att->attribute2))
             $att->attribute2->load('attributeClass');
            */
        }
        return $product_att;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'parent_id' => 'required|numeric',
            'active' => 'nullable|boolean',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $attribute = Attribute::find($validated['id']);
            if ($attribute) {
                $attribute->delete();
                return response()->json(["message" => "Done"]);
            }
            return response()->json(["message" => "NOT_FOUND"], 404);
        }

        if (!isset($validated['id'])) {
            $validated['order'] = Attribute::where('parent_id', $validated['parent_id'])->max('order') + 1;
            if (Attribute::where([
                ['parent_id', '=', $validated['parent_id']],
                ['order', '=', $validated['order']]
            ])->first())
                return response()->json(["message" => "ORDER_EXIST"]);

            if (Attribute::where([
                ['parent_id', '=', $validated['parent_id']],
                ['name_ar', '=', $validated['name_ar']]
            ])->first())
                return response()->json(["message" => "NAME_AR_EXIST"]);

            if (Attribute::where([
                ['parent_id', '=', $validated['parent_id']],
                ['name_en', '=', $validated['name_en']]
            ])->first())
                return response()->json(["message" => "NAME_EN_EXIST"]);

            Attribute::create($validated);
        } else {
            $attribute = Attribute::find($validated['id']);
            if (!$attribute) {
                return response()->json(["message" => "NOT_FOUND"], 404);
            }

            if (Attribute::where([
                ['id', '!=', $validated['id']],
                ['parent_id', '=', $validated['parent_id']],
                ['name_ar', '=', $validated['name_ar']]
            ])->first())
                return response()->json(["message" => "NAME_AR_EXIST"]);

            if (Attribute::where([
                ['id', '!=', $validated['id']],
                ['parent_id', '=', $validated['parent_id']],
                ['name_en', '=', $validated['name_en']]
            ])->first())
                return response()->json(["message" => "NAME_EN_EXIST"]);
            $attribute->name_ar = $validated['name_ar'];
            $attribute->name_en = $validated['name_en'];
            $attribute->cost = $validated['cost'] ?? $attribute->cost;
            $attribute->price = $validated['price'] ?? $attribute->price;
            $attribute->active = $validated['active'];
            $attribute->save();
        }

        return response()->json(["message" => "Done"]);
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product  = Modifier::find($id);
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
