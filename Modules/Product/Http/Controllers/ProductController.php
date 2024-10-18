<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductModifier;
use Modules\Product\Models\Product_Attribute;

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
        return view('product::product.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'order' => 'required|numeric',
            'category_id' => 'required|numeric',
            'subcategory_id' => 'required|numeric',
            'active' => 'nullable|boolean',
            'SKU'=> 'nullable|string',
            'barcode'=> 'nullable|string',
            'cost'=> 'required|numeric',
            'price'=> 'required|numeric',
            'description_ar'=> 'nullable|string',
            'description_en'=> 'nullable|string',
            'class'=> 'nullable|string',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string',
            'sold_by_weight' => 'nullable|boolean',
            'track_serial_number' => 'nullable|boolean',
            'image_file' =>'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image' =>'nullable|string',
            'color' => 'nullable|string',
            'commissions' => 'nullable|numeric',
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

        if ($request->hasFile('image_file')) 
          {
            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            // Get the uploaded file
            $file = $request->file('image_file');
    
            // Define the path based on the tenant's ID
            $filePath =  '/product/images';
    
            // Store the file
            $fileExtension = $file->getClientOriginalExtension();
            $file->storeAs($filePath, $product->id . '.' . $fileExtension , 'public'); // Store in public disk
    
            // Optionally save the file path to the database
            $product->image = 'storage/'. 'tenant'. $tenantId  .$filePath . '/' . $product->id . '.' . $fileExtension ;
          }
        $product->save();
        if(isset($request["modifiers"]))
        {
            foreach ($request["modifiers"] as $modifier) {
                if(isset($modifier['id'])){
                    $mod = ProductModifier::find($modifier['id']);
                    $mod->active = $modifier['active'];
                    $mod->default = $modifier['default'];
                    $mod->required = $modifier['required'];
                    $mod->min_modifiers = $modifier['min_modifiers'];
                    $mod->max_modifiers = $modifier['max_modifiers'];
                    $mod->display_order = $modifier['display_order'];
                    $mod->button_display = $modifier['button_display'];
                    $mod->modifier_display = $modifier['modifier_display'];
                    $mod['free_quantity'] = 0;
                    $mod['free_type'] = 0;
                    $mod->save();
                }
                else {
                    $modifier['free_quantity'] = 0;
                    $modifier['free_type'] = 0;
                    ProductModifier::create($modifier);
                }
            }
        }   
        if(isset($request["attributeMatrix"]))
        {
            $oldAttributes = Product_Attribute::where('product_id' , $validated['id'])->get();
            foreach ( $oldAttributes as $oldAttribute)
            {
                $oldAttribute->delete();
            }
            foreach ($request["attributeMatrix"] as $attribute) 
            {
                $att = [];
                $att['product_id'] =  $validated['id'];
                $att['attribute_id1'] = $attribute['attribute1']['id'];
                $att['attribute_id2'] = isset($attribute['attribute2'])? $attribute['attribute2']['id']: null;
                $att['name_ar'] = $attribute['name_ar'];
                $att['name_en'] = $attribute['name_en'];
                $att['barcode'] = isset($attribute['barcode'])? $attribute['barcode']: null;
                $att['SKU'] = isset($attribute['SKU'])? $attribute['SKU']: null;
                $att['price'] = $attribute['price'];
                $att['starting'] = isset($attribute['starting'])? $attribute['starting']: null;
                Product_Attribute::create($att);
            }
        }   
    }
        else
        {    
        $product= Product::create($validated);

        if(isset($request["modifiers"]))
        {
            
            foreach ($request["modifiers"] as $modifier) {
                $modifier['product_id'] = $product->id;
                $modifier['free_quantity'] = 0;
                $modifier['free_type'] = 0;
                ProductModifier::create($modifier);
            }
       
        }

        if(isset($request["attributeMatrix"]))
        {
            foreach ($request["attributeMatrix"] as $attribute) 
            {
                $att = [];
                $att['product_id'] =  $product->id;
                $att['attribute_id1'] = $attribute['attribute1']['id'];
                $att['attribute_id2'] = isset($attribute['attribute2'])? $attribute['attribute2']['id']: null;
                $att['name_ar'] = $attribute['name_ar'];
                $att['name_en'] = $attribute['name_en'];
                $att['barcode'] = isset($attribute['barcode'])? $attribute['barcode']: null;
                $att['SKU'] = isset($attribute['SKU'])? $attribute['SKU']: null;
                $att['price'] = $attribute['price'];
                $att['starting'] = isset($attribute['starting'])? $attribute['starting']: null;
                Product_Attribute::create($att);
            }
        }
     
        if ($request->hasFile('image_file')) {

            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            // Get the uploaded file
            $file = $request->file('image_file');
    
            // Define the path based on the tenant's ID
            $filePath =  '/product/images';
    
            // Store the file
            $fileExtension = $file->getClientOriginalExtension();
            $file->storeAs($filePath, $product->id . '.' . $fileExtension , 'public'); // Store in public disk
    
            // Optionally save the file path to the database
            $product->image = 'storage/'. 'tenant'. $tenantId  .$filePath . '/' . $product->id . '.' . $fileExtension ;
            $product->save();
        } 
        else
        {
          $product->image =  null;
        }  
    }
}
        return response()->json(["message"=>"Done"]);
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
         $product->modifiers = $product->modifiers;
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
