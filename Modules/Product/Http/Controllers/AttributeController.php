<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Product_Attribute;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::attribute.index' ); 

    }

    public function getProductMatrix($id)
    {
      $product_att = Product_Attribute::where('product_id', $id)->get();

      foreach($product_att as $att)
       {
            $att->load(['attribute1','attribute2']);
    
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
            'order' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $Attribute = Attribute::find($validated['id']); 
            $Attribute->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(!isset($validated['id']))
        {
            $Attribute = Attribute::where([['parent_id', '=', $validated['parent_id']],
                                        ['order', '=', $validated['order']]])->first();
            if($Attribute != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $Attribute = Attribute::where([['parent_id', '=', $validated['parent_id']],
                                        ['name_ar', '=', $validated['name_ar']]])->first();
            if($Attribute != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $Attribute = Attribute::where([['parent_id', '=', $validated['parent_id']],
                                        ['name_en', '=', $validated['name_en']]])->first();
            if($Attribute != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
                Attribute::create($validated);
        }
        else
        {
            //dd($validated['id'].' '.$validated['class_id'].' '.$validated['name_ar']);
            $Attribute = Attribute::where([
                ['id', '!=', $validated['id']],
                ['parent_id', '=', $validated['parent_id']],
                ['order', '=', $validated['order']]])->first();
            if($Attribute != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $Attribute = Attribute::where([
                ['id', '!=', $validated['id']],
                ['parent_id', '=', $validated['parent_id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($Attribute != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $Attribute = Attribute::where([
                ['id', '!=', $validated['id']],
                ['parent_id', '=', $validated['parent_id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($Attribute != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $Attribute = Attribute::find($validated['id']);
            $Attribute->name_ar  = $validated['name_ar'];
            $Attribute->name_en  = $validated['name_en'];
            $Attribute->cost     = $validated['cost'];
            $Attribute->price    = $validated['price'];
            $Attribute->active   = $validated['active'];
            $Attribute->order   = $validated['order'];
            $Attribute->save();
        }
        return response()->json(["message"=>"Done"]);
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = Modifier::find($id);

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
