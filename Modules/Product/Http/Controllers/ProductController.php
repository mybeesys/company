<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
          // Access all records from the product_items table
          $Product = Product::all();

          // Return the items as a JSON response (or to a view)
          return response()->json($Product);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('product::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'category_id' => 'required|numeric',
            'subcategory_id' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'SKU'=> 'required|string',
            'barcode'=> 'required|string',
            'cost'=> 'required|numeric',
            'price'=> 'required|numeric',
            'description_ar'=> 'nullable|string',
            'description_en'=> 'nullable|string',
            'class'=> 'required|string',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $product = Product::find($validated['id']); 
            $product->delete();
            return response()->json(["message"=>"Done"]);
        }
       else
       { 
        $item = Product::create($validated);
        return response()->json(["message"=>"Done"]);
       }

    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = Product::find($id);

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
        return view('product::edit');
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
