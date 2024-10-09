<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function localization()
     {
         return response()->json(__('product::messages'));
     }

    public function index()
    {
        return view('product::product.index' ); 
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
            'method' => 'nullable|string',
            'sold_by_weight' => 'nullable|boolean',
            'track_serial_number' => 'nullable|boolean',
            'image' =>'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'color' => 'nullable|string',
            'commissions' => 'nullable|boolean',
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $product = Product::find($validated['id']); 
            $product->delete();
            return response()->json(["message"=>"Done"]);
        }
       else
       { 
        if(isset($validated['id']))
        {
          $product = Product::find($validated['id']); 
          $product->name_ar = $validated['name_ar'];
          $product->name_en = $validated['name_en'];
          $product->description_ar = isset($validated['description_ar'])? $validated['description_ar'] :"";
          $product->description_en = isset($validated['description_en'])? $validated['description_en']:"";
          $product->SKU = isset($validated['SKU'])? $validated['SKU'] :  $product->SKU;
          $product->barcode =isset($validated['barcode'])? $validated['barcode']: $product->barcode;
          $product->category_id = $validated['category_id'];
          $product->subcategory_id = $validated['subcategory_id'];
          $product->active = $validated['active'];
          $product->sold_by_weight = $validated['sold_by_weight'];
          $product->track_serial_number = $validated['track_serial_number'];
          $product->commissions = isset($validated['commissions'])? $validated['commissions']: $product->commissions;
          $product->price = $validated['price'];
          $product->cost = $validated['cost'];
          $product->color = isset($validated['color'])?$validated['color']: $product->color ;

          if ($request->hasFile('image')) {
            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            // Get the uploaded file
            $file = $request->file('image');
    
            // Define the path based on the tenant's ID
            $filePath = 'tenants/' . $tenantId . '/product/images';
    
            // Store the file
            $fileExtension = $file->getClientOriginalExtension();
            $file->storeAs($filePath, $product->id . '.' . $fileExtension , 'public'); // Store in public disk
    
            // Optionally save the file path to the database
            $product->image = $filePath . '/' . $product->id . '.' . $fileExtension ;
        }  

          $product->save();
        }
        else
        {
            $product= Product::create($validated);
     
        if ($request->hasFile('image')) {

            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            // Get the uploaded file
            $file = $request->file('image');
    
            // Define the path based on the tenant's ID
            $filePath = 'tenants/' . $tenantId . '/product/images';
    
            // Store the file
            $fileExtension = $file->getClientOriginalExtension();
            $file->storeAs($filePath, $product->id . '.' . $fileExtension , 'public'); // Store in public disk
    
            // Optionally save the file path to the database
            $product->image = $filePath . '/' . $product->id . '.' . $fileExtension ;
            $product->save();
        }   
        } 
      
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
         $product  = Product::find($id);
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
