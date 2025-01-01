<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductModifier;
use Modules\Product\Models\Product_Attribute;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\ProductCombo;
use Modules\Product\Models\ProductComboItem;
use Modules\Product\Models\ProductLinkedComboItem;
use Modules\Product\Models\ProductLinkedComboUpcharge;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryOperationItem;
use Modules\Inventory\Models\Prep;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\ProductTax;
use Modules\Product\Models\UnitTransfer;

class ProductController extends Controller
{
    protected $requetsValidator = [
        'name_ar' => 'required|string|max:255',
        'name_en' => 'required|string',
        'order' => 'required|numeric',
        'category_id' => 'required|numeric',
        'subcategory_id' => 'required|numeric',
        'tax_id' => 'nullable|numeric',
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
        'recipe_yield' => 'nullable|numeric',
        'prep_recipe' => 'nullable|boolean',
        'group_combo' => 'nullable|boolean',
        'set_price' => 'nullable|boolean',
        'use_upcharge' => 'nullable|boolean',
        'linked_combo' => 'nullable|boolean',
        'promot_upsell' => 'nullable|numeric'
    ];


    public function all(){
        $products = Product::all();
        return response()->json($products);
     }

    public function listRecipe($id, Request $request)
    {
        $key = $request->query('with_ingredient', '');
        $recipes = null;
        if(isset($key) && $key='Y'){
            $recipes = RecipeProduct::where([['product_id', '=', $id]])->get();
            $resRecipes = [];
            foreach ($recipes as $recipe) {
                $newItem = $recipe->toArray();
                if($recipe->item_type == 'p'){
                    $newItem["product_id"] = $recipe->item_id.'-p';
                    $prod = $recipe->products->toArray();
                    $prod["id"] =  $recipe->item_id.'-p';
                    if(isset($recipe->products))
                    $prod["unitTransfers"] = $recipe->products->unitTransfers;
                    $newItem["products"] =$prod;
                }
                if($recipe->item_type == 'i'){
                    $newItem["product_id"] = $recipe->item_id.'-i';
                    $ingr = $recipe->ingredients->toArray();
                    $ingr["id"] =  $recipe->item_id.'-i';
                    $ingr["unitTransfers"] = $recipe->ingredients->unitTransfers;
                    $newItem["products"] =$ingr;
                }
                $resRecipes [] =$newItem;
            }
        }
        else{
            $resRecipes = RecipeProduct::where([['product_id', '=', $id]])->get();
            foreach ( $resRecipes as $rec) 
            {
                $rec->newid = $rec->item_id."-".$rec->item_type;
            }
        }
        return response()->json($resRecipes);
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
        $product  = new Product();
        $product->group_combo = 0;
        $product->linked_combo = 0;
        $product->set_price = 0;
        $product->use_upcharge = 0;
        $product->combos = [];
        $product->linkedCombos = [];
        $product->establishments = [];
        $product->recipe = [];
        $product->attributes = [];
        //$product->taxIds = [];
        $product->active = 1;
        return view('product::product.create', compact('product'));
    }

    private function validateInUse($product_id){
        $product = InventoryOperationItem::where([['product_id', '=', $product_id]])->first();
        if($product != null)
            return response()->json(["message"=>"PRODUCT_USED_INVENTORY"]);
        $product = ProductComboItem::where([['item_id', '=', $product_id]])->first();
        if($product != null)
            return response()->json(["message"=>"PRODUCT_USED_COMBO"]);
        $product = RecipeProduct::where([['item_id', '=', $product_id],
                                        ['item_type', '=', 'p']])->first();
        if($product != null)
            return response()->json(["message"=>"PRODUCT_USED_RECIPE"]);
        $product = Prep::where([['product_id', '=', $product_id]])->first();
        if($product != null)
            return response()->json(["message"=>"PRODUCT_USED_PREP"]);
        return null;
    }

    private function validateProduct($id, $product){
        $checkResult = [];
        $uniqueFields = ['name_ar', 'name_en'];
        if(isset($product['SKU']))
            $uniqueFields [] = 'SKU';
        if($id !=null)
            $query = Product::where('id', '!=', $id);
        else
            $query = Product::whereRaw('1 = 1');
        $query = $query->where(function($subQuery) use($uniqueFields, $product) {
            for ($i = 0; $i < count($uniqueFields); $i++) {
                $subQuery = $subQuery->orWhere($uniqueFields[$i], '=', $product[$uniqueFields[$i]]);
            }
        });
        $products = $query->get();
        for ($i = 0; $i < count($uniqueFields); $i++) {
            $res = array_filter($products->toArray(), function($prod)use($product, $uniqueFields, $i) {
                return $prod[$uniqueFields[$i]] == $product[$uniqueFields[$i]]; // Keep only even numbers
            });
            if(count($res)>0)
                $checkResult [] = $uniqueFields[$i];
        }
        if(count($checkResult)>0){
            return ['message' => 'UNIQUE',
            'data' => $checkResult];
        } 
        return $checkResult;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->requetsValidator);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $validateUsing = $this->validateInUse($validated['id']);
            if($validateUsing != null)
                return $validateUsing;
            $product = Product::find($validated['id']);
            $product->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(isset($validated['id']))
        {
            $res = $this->validateProduct($validated['id'], $validated);
            if(count($res) > 0)
                return $res;
            $this->saveProduct($validated, $request);
        }
        else
        { 
            $res = $this->validateProduct(null, $validated);
            if(count($res) > 0)
                return $res;
            $this->createProduct($validated, $request);
        }
        return response()->json(["message"=>"Done"]);
    }

    protected function saveProduct($validated, $request){
        $product = Product::find($validated['id']); 
        $product->name_ar = $validated['name_ar'];
        $product->name_en = $validated['name_en'];
        $product->description_ar = isset($validated['description_ar'])? $validated['description_ar'] :"";
        $product->description_en = isset($validated['description_en'])? $validated['description_en']:"";
        $product->SKU = isset($validated['SKU'])? $validated['SKU'] :  $product->SKU;
        $product->barcode =isset($validated['barcode'])? $validated['barcode']: $product->barcode;
        $product->category_id = $validated['category_id'];
        $product->tax_id = $validated['tax_id'];
        $product->subcategory_id = $validated['subcategory_id'];
        $product->active = $validated['active'];
        $product->sold_by_weight = $validated['sold_by_weight'];
        $product->track_serial_number = $validated['track_serial_number'];
        $product->commissions = isset($validated['commissions'])? $validated['commissions']: $product->commissions;
        $product->price = $validated['price'];
        $product->cost = $validated['cost'];
        $product->color = isset($validated['color'])?$validated['color']: $product->color ;  
        $product->prep_recipe = isset($validated['prep_recipe'])? $validated['prep_recipe']: $product->prep_recipe;
        $product->recipe_yield = isset($validated['recipe_yield'])? $validated['recipe_yield']: $product->recipe_yield;
        $product->group_combo = $validated['group_combo'] ?? 0;
        $product->set_price = isset($validated['set_price'])? $validated['set_price'] : null;
        $product->use_upcharge = isset($validated['use_upcharge']) ?$validated['use_upcharge'] : null;
        $product->linked_combo = $validated['linked_combo'] ?? 0;
        $product->promot_upsell = isset($validated['promot_upsell']) ?$validated['promot_upsell'] : null ;
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
       // DB::transaction(function () use ($product, $request) {
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
            $oldRecipe = RecipeProduct::where('product_id' , $product->id)->get();
            foreach ($oldRecipe as $recipe)
            {
                $recipe->delete();
            }
            if(isset($request["recipe"]))
            {
                $order = 0 ;
                foreach ($request["recipe"] as $recipe) 
                {
                    $rec = [];
                    $rec['product_id'] =  $product->id;
                    $rec['quantity'] = $recipe['quantity'];
                    $recipeIngredient = explode("-",$recipe['newid']);
                    $rec['item_id'] = $recipeIngredient[0];
                    $rec['item_type'] = $recipeIngredient[1];
                    $rec["unit_transfer_id"] = $recipe["unit_transfer"]["id"];
                    $rec['order'] =  $order++;
                    RecipeProduct::create($rec);
                }
            }  
            
            $oldAttributes = Product_Attribute::where('product_id' , $product->id)->get();
            foreach ( $oldAttributes as $oldAttribute)
            {
                $oldAttribute->delete();
            }
            
            if(isset($request["attributes"]))
            {
                foreach ($request["attributes"] as $attribute) 
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
            
            $oldUnites = UnitTransfer::where('product_id' , $product->id)->get();
        
            if(isset($request["transfer"])){
                $ids=[];
                $insertedIds=[];
                $updatedTransfers = [];
                $requestIds = array_map(function($item) {
                    return $item["id"];
                }, $request["transfer"]);
                UnitTransfer::where('product_id', '=',  $product->id)->whereNotIn('id', $requestIds)->delete();  
                foreach ($oldUnites  as $old){
                    $newid = [];
                    $newid['oldId'] = $old['id'];
                    $newid['newId'] = $old['id'];
                    $ids[] = $newid ;
                }
                foreach ($request["transfer"] as $transfer){
                    if($transfer['id'] <= 0)
                    {
                        $newid = [];
                        $inserted = [];
                        $tran = [];
                        $newid['oldId'] =  $transfer['id'];
                        $tran['product_id'] =  $product->id;
                        $tran['transfer'] = isset($transfer['transfer']) && $transfer['transfer'] != -100 ? $transfer['transfer'] :null;
                        $tran['primary'] = isset($transfer['primary']) &&  $transfer['primary'] == true? 1 : 0;
                        $tran['unit1'] = $transfer['unit1'];
                        $tran['unit2'] = null ;//$transfer['unit2'] != -100? $transfer['unit2'] : null;
                        $id = UnitTransfer::create($tran)->id;
                        $inserted['id'] = $id;
                        $inserted['unit2'] = $transfer['unit2'];
                        $newid['newId'] =  $id;
                        $ids[] = $newid ;
                        $insertedIds[] = $inserted;
                    }
                    else if(!isset($transfer['unit2'])){
                        $updatedTransfer = UnitTransfer::find($transfer['id']);
                        $updatedTransfer['unit1'] = $transfer['unit1'];
                        $updatedTransfer->save();
                    }
                    else{
                        $updatedTransfer = UnitTransfer::find($transfer['id']);
                        $updatedTransfer['unit1'] = $transfer['unit1'];
                        $updatedTransfer['unit2'] = $transfer['unit2'];
                        $updatedTransfer['primary'] = $transfer['primary'];
                        $updatedTransfer['transfer'] = $transfer['transfer'];
                        $updatedTransfer->save();
                    }
                }
                foreach ($insertedIds as $transfer){
                    foreach($ids as $updateId)
                    {
                        if($transfer['unit2'] == $updateId['oldId'] )
                        {
                            $updateObject = UnitTransfer::find($transfer['id']);
                            $updateObject->unit2 =  $updateId['newId'];
                            $updateObject->save();
                        }
                    } 
                }  
            }
            ProductCombo::where('product_id', '=', $product->id)->delete();
            if(isset($request["combos"]))
            {
                foreach ($request["combos"] as $combo) {
                    $productCombo = new ProductCombo();
                    $productCombo->product_id = $product->id;
                    $productCombo->name_ar = $combo["name_ar"];
                    $productCombo->name_en = $combo["name_en"];
                    $productCombo->combo_saving = $combo["combo_saving"];
                    $productCombo->quantity = $combo["quantity"];
                    $productCombo->price = isset($combo["price"]) ? $combo["price"] : null;
                    $productCombo->save();
                    if(isset($combo["products"])){
                        ProductComboItem::where('combo_id', '=', $productCombo->id)->delete();
                        foreach ($combo["products"] as $productId) {
                            $comboItem = new ProductComboItem();
                            $comboItem->item_id = $productId;
                            $comboItem->combo_id = $productCombo->id;
                            if(isset($combo['upchargePrices'])){
                                $index = array_search($productId, array_column($combo["upchargePrices"], 'product_id'));
                                if ($index !== false) {
                                    $comboItem->price= isset($combo['upchargePrices'][$index]["price"]) ? $combo['upchargePrices'][$index]["price"] : null;
                                }
                            }
                            $comboItem->save();
                        }
                    }
                }
            }
            if(isset($request["linkedCombos"]))
            {
                ProductLinkedComboItem::where('product_id', '=', $product->id)->delete();
                foreach ($request["linkedCombos"] as $linkedCombo) {
                    $productLinkedComboItem = new ProductLinkedComboItem();
                    $productLinkedComboItem->product_id = $product->id;
                    $productLinkedComboItem->linked_combo_id = $linkedCombo["linked_combo_id"];
                    $productLinkedComboItem->save();
                    if(isset($linkedCombo["upchargePrices"])){
                        foreach ($linkedCombo["upchargePrices"] as $upchargePrice) {
                            $upcharge = new ProductLinkedComboUpcharge();
                            $upcharge->product_id = $upchargePrice["product_id"];
                            $upcharge->combo_id =  $upchargePrice["combo_id"];
                            $upcharge->price =  $upchargePrice["price"];
                            $upcharge->product_combo_id =  $productLinkedComboItem->id;
                            $upcharge->save();
                        }
                    }
                }
            }
            EstablishmentProduct::where('product_id', '=', $product->id)->delete();
            if(isset($request["establishments"]))
            {
                foreach ($request["establishments"] as $newEstablishment) {
                    
                    $establishment = new EstablishmentProduct();
                    $wh = $newEstablishment['establishment'];
                    $establishment->product_id = $product->id;
                    $establishment->establishment_id = $wh["id"];
                    $establishment->save();
                }
            }
            // ProductTax::where('product_id', '=', $product->id)->delete();
            // if(isset($request["taxIds"]))
            // {
            //     foreach ($request["taxIds"] as $newTax) {
                    
            //         $tax = new ProductTax();
            //         $tax->product_id = $product->id;
            //         $tax->tax_id = $newTax;
            //         $tax->save();
            //     }
            // }
        //});
    }

    protected function createProduct($validated, $request){
        DB::transaction(function () use ($validated, $request) {
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
            if(isset($request["attributes"]))
            {
                foreach ($request["attributes"] as $attribute) 
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
            if(isset($request["transfer"]))
            {
                $ids=[];
                $insertedIds=[];
                foreach ($request["transfer"] as $transfer) 
                {
                    $newid = [];
                    $inserted = [];
                    $tran = [];
                    $newid['oldId'] =  $transfer['id'];
                    $tran['product_id'] =  $product->id;
                    $tran['transfer'] = isset($transfer['transfer']) && $transfer['transfer'] != -100 ? $transfer['transfer'] :null;
                    $tran['primary'] = isset($transfer['primary']) &&  $transfer['primary'] == true? 1 : 0;
                    $tran['unit1'] = $transfer['unit1'];
                    $tran['unit2'] = null ;//$transfer['unit2'] != -100? $transfer['unit2'] : null;
                    $id = UnitTransfer::create($tran)->id;
                    $inserted['id'] = $id;
                    $inserted['unit2'] = $transfer['unit2'];
                    $newid['newId'] =  $id;
                    $ids[] = $newid ;
                    $insertedIds[] = $inserted;
                }
                foreach ($insertedIds as $transfer) 
                {
                    foreach($ids as $updateId)
                    {
                    if($transfer['unit2'] == $updateId['oldId'] )
                    {
                        $updateObject = UnitTransfer::find($transfer['id']);
                        $updateObject->unit2 =  $updateId['newId'];
                        $updateObject->save();
                    }
                    } 
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
            if(isset($request["recipe"]))
            {
                $order = 0 ;
                foreach ($request["recipe"] as $recipe) 
                {
                    $rec = [];
                    $rec['product_id'] =  $validated['id'];
                    $rec['quantity'] = $recipe['quantity'];
                    $recipeIngredient = explode("-",$recipe['newid']);
                    $rec['item_id'] = $recipeIngredient[0];
                    $rec['item_type'] = $recipeIngredient[1];
                    $rec['order'] =  $order++;
                    RecipeProduct::create($rec);
                }
            }   
            
            if(isset($request["combos"]))
            {
                ProductCombo::where('product_id', '=', $product->id)->delete();
                foreach ($request["combos"] as $combo) {
                    $productCombo = new ProductCombo();
                    $productCombo->product_id = $product->id;
                    $productCombo->name_ar = $combo["name_ar"];
                    $productCombo->name_en = $combo["name_en"];
                    $productCombo->combo_saving = $combo["combo_saving"];
                    $productCombo->quantity = $combo["quantity"];
                    $productCombo->price = isset($combo["price"]) ? $combo["price"] : null;
                    $productCombo->save();
                    if(isset($combo["products"])){
                        ProductComboItem::where('combo_id', '=', $productCombo->id)->delete();
                        foreach ($combo["products"] as $productId) {
                            $comboItem = new ProductComboItem();
                            $comboItem->item_id = $productId;
                            $comboItem->combo_id = $productCombo->id;
                            if(isset($combo['upchargePrices'])){
                                $index = array_search($productId, array_column($combo["upchargePrices"], 'product_id'));
                                if ($index !== false) {
                                    $comboItem->price= isset($combo['upchargePrices'][$index]["price"]) ? $combo['upchargePrices'][$index]["price"] : null;
                                }
                            }
                            $comboItem->save();
                        }
                    }
                }
            }
            if(isset($request["linkedCombos"]))
            {
                ProductLinkedComboItem::where('product_id', '=', $product->id)->delete();
                foreach ($request["linkedCombos"] as $linkedCombo) {
                    $productLinkedComboItem = new ProductLinkedComboItem();
                    $productLinkedComboItem->product_id = $product->id;
                    $productLinkedComboItem->linked_combo_id = $linkedCombo["linked_combo_id"];
                    $productLinkedComboItem->save();
                    if(isset($linkedCombo["upchargePrices"])){
                        ProductLinkedComboUpcharge::where('product_combo_id', '=', $productLinkedComboItem->linked_combo_id)->delete();
                        foreach ($linkedCombo["upchargePrices"] as $upchargePrice) {
                            $upcharge = new ProductLinkedComboUpcharge();
                            $upcharge->product_id = $upchargePrice["product_id"];
                            $upcharge->combo_id =  $upchargePrice["combo_id"];
                            $upcharge->combo_id =  $productLinkedComboItem->id;
                            $upcharge->save();
                        }
                    }
                }
            }
            if(isset($request["establishments"]))
            {
                foreach ($request["establishments"] as $newEstablishment) {
                    
                    $establishment = new EstablishmentProduct();
                    $wh = $newEstablishment['establishment'];
                    $establishment->product_id = $product->id;
                    $establishment->establishment_id = $wh["id"];
                    $establishment->save();
                }
            }
            // if(isset($request["taxIds"]))
            // {
            //     foreach ($request["taxIds"] as $newTax) {
                    
            //         $tax = new ProductTax();
            //         $tax->product_id = $product->id;
            //         $tax->tax_id = $newTax;
            //         $tax->save();
            //     }
            // }
        });
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
        $product  = Product::with(['establishments' => function ($query) {
            $query->with('establishment');
        }])->with(['recipe' => function ($query) {
            $query->with('unitTransfer');
        }])->with(['attributes' => function ($query) {
            $query->with('attribute1');
            $query->with('attribute2');
        }])->find($id);
        foreach ( $product->recipe as $rec) 
        {
            $rec->newid = $rec->item_id."-".$rec->item_type;
            $rec->cost = $rec->detail->cost;
        }
        foreach ( $product->attributes as $attr) 
        {
            $attr->attribute1Name_en = $attr->attribute1->name_en;
            $attr->attribute1Name_ar = $attr->attribute1->name_ar;
            $attr->attribute2Name_en = $attr->attribute2->name_en;
            $attr->attribute2Name_ar = $attr->attribute2->name_ar;
        }
        $product->modifiers = $product->modifiers;
        $product->combos = $product->combos;
        // $product->taxIds = array_map(function($item) {
        //     return $item["tax_id"];
        // }, $product->taxes->toArray());
        foreach ($product->combos as $d) {
           $d->products = array_map(function($item) {
               return $item["item_id"];
           }, $d->items->toArray());
           $d->upchargePrices = array_map(function($item) {
               $upchargePrice = [];
               $upchargePrice["product_id"] = $item["item_id"];
               $upchargePrice["price"] = $item["price"];
               return $upchargePrice;
           }, $d->items->toArray());
       }
       $product->linkedCombos = $product->linkedCombos;
       foreach ($product->linkedCombos as $d1) {
           $d1->upchargePrices = array_map(function($item) use ($d1) {
               $upchargePrice = [];
               $upchargePrice["product_id"] = $item["product_id"];
               $upchargePrice["combo_id"] = $item["combo_id"];
               $upchargePrice["product_combo_id"] = $d1["linked_combo_id"];
               $upchargePrice["price"] = $item["price"];
               return $upchargePrice;
           }, $d1->upcharges->toArray());
       }
        return view('product::product.edit', compact('product'));
    }

    public function searchProducts(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $products = Product::where('name_ar', 'like', '%' . $key . '%')
                            ->orWhere('name_en', 'like', '%' . $key . '%')
                            ->take(10)
                            ->get();
        $ingredients = Ingredient::where('name_ar', 'like', '%' . $key . '%')
                            ->orWhere('name_en', 'like', '%' . $key . '%')
                            ->take(10)
                            ->get();
        $products = $products->map(function ($product) {
            $newProduct = $product->toArray();
            $newProduct["id"] = $product["id"].'-p'; // Set the value of 'item_type'
            return $newProduct;
        });
        $ingredients = $ingredients->map(function ($ingredient) {
            $newIngredient = $ingredient->toArray();
            $newIngredient["id"] = $ingredient["id"].'-i'; // Set the value of 'item_type'
            return $newIngredient;
        });
        $result = array_merge($ingredients->toArray() , $products->toArray());
        return response()->json($result);
    }

    public function searchPrepProducts(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $products = Product::where(function ($query) use($key) {
                                $query->where('name_ar', 'like', '%' . $key . '%')                // (status = 'active'
                                    ->orWhere('name_en', 'like', '%' . $key . '%') ;           // OR status = 'pending')
                            })
                            ->whereIn('id', function ($query) {
                                $query->select('product_id')
                                    ->from('product_recipe_products');
                            })
                            ->take(10)
                            ->get();
        $products = $products->map(function ($product) {
            $product->item_type = 'p'; // Set the value of 'item_type'
            return $product;
        });
        return response()->json($products);
    }
}
