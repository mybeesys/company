<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\TreeBuilder;

class ModifierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::modifier.index' ); 
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
            'class_id' => 'nullable|numeric',
            'cost'=> 'nullable|numeric',
            'price'=> 'nullable|numeric',
            'tax_id' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'order' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $modifier = Modifier::find($validated['id']); 
            $modifier->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(!isset($validated['id']))
        {
            $modifier = Modifier::where([['class_id', '=', $validated['class_id']],
                                        ['order', '=', $validated['order']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $modifier = Modifier::where([['class_id', '=', $validated['class_id']],
                                        ['name_ar', '=', $validated['name_ar']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $modifier = Modifier::where([['class_id', '=', $validated['class_id']],
                                        ['name_en', '=', $validated['name_en']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            Modifier::create($validated);
        }
        else
        {
            //dd($validated['id'].' '.$validated['class_id'].' '.$validated['name_ar']);
            $modifier = Modifier::where([
                ['id', '!=', $validated['id']],
                ['class_id', '=', $validated['class_id']],
                ['order', '=', $validated['order']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $modifier = Modifier::where([
                ['id', '!=', $validated['id']],
                ['class_id', '=', $validated['class_id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $modifier = Modifier::where([
                ['id', '!=', $validated['id']],
                ['class_id', '=', $validated['class_id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $modifier = Modifier::find($validated['id']);
            $modifier->name_ar  = $validated['name_ar'];
            $modifier->name_en  = $validated['name_en'];
            $modifier->cost     = $validated['cost'];
            $modifier->price    = $validated['price'];
            $modifier->tax_id   = $validated['tax_id'];
            $modifier->active   = $validated['active'];
            $modifier->order   = $validated['order'];
            $modifier->save();
        }
        return response()->json(["message"=>"Done"]);
    }

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
