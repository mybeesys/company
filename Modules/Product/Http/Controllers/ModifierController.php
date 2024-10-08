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
            'class_id' => 'required|numeric',
            'cost'=> 'required|numeric',
            'price'=> 'required|numeric',
            'active' => 'nullable|boolean',
            'order' => 'nullable|numeric'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $product = Modifier::find($validated['id']); 
            $product->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(!isset($validated['id']))
        {
            Modifier::create($validated);
        }
        else
        {
            $modifier = Modifier::find($validated['id']);
            $modifier->name_ar  = $validated['name_ar'];
            $modifier->name_en  = $validated['name_en'];
            $modifier->cost     = $validated['cost'];
            $modifier->price    = $validated['price'];
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
