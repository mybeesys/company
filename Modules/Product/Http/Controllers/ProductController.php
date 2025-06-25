<?php

namespace Modules\Product\Http\Controllers;

use Exception;
use App\Helpers\TaxHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductModifier;
use Modules\Product\Models\Product_Attribute;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\ProductCombo;
use Modules\Product\Models\ProductComboItem;
use Modules\Product\Models\ProductLinkedComboItem;
use Modules\Product\Models\ProductLinkedComboUpcharge;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\TransactionSellLine;
use Modules\Inventory\Models\Prep;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\PriceTier;
use Modules\Product\Models\ProductPriceTier;
use Modules\Product\Models\ProductTax;
use Modules\Product\Models\RecipeModifier;
use Modules\Product\Models\UnitTransfer;
use Illuminate\Support\Facades\Log;
use function Laravel\Prompts\error;

class ProductController extends Controller
{
    protected $requetsValidator = [
        'name_ar' => 'required|string|max:255',
        'name_en' => 'required|string',
        'order' => 'nullable|numeric',
        'category_id' => 'required|numeric',
        'subcategory_id' => 'required|numeric',
        'tax_id' => 'nullable|numeric',
        'active' => 'nullable|boolean',
        'SKU' => 'nullable|string',
        'barcode' => 'nullable|string',
        'cost' => 'required|numeric',
        'price' => 'required|numeric',
        'description_ar' => 'nullable|string',
        'description_en' => 'nullable|string',
        'class' => 'nullable|string',
        'id' => 'nullable|numeric',
        'method' => 'nullable|string',
        'sold_by_weight' => 'nullable|boolean',
        'track_serial_number' => 'nullable|boolean',
        'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'image' => 'nullable|string',
        'color' => 'nullable|string',
        'commissions' => 'nullable|numeric',
        'recipe_yield' => 'nullable|numeric',
        'prep_recipe' => 'nullable|boolean',
        'group_combo' => 'nullable|boolean',
        'set_price' => 'nullable|boolean',
        'use_upcharge' => 'nullable|boolean',
        'linked_combo' => 'nullable|boolean',
        'promot_upsell' => 'nullable|numeric',
        // 'for_sell' => 'required|boolean',
        'preparation_time' => 'nullable|numeric',
        'calories' => 'nullable|numeric',
        'show_in_menu' => 'required|boolean',
    ];


    public function all()
    {
        $products = Product::all();
        return response()->json($products);
    }

    public function listRecipe($id, Request $request)
    {
        $key = $request->query('with_ingredient', '');
        $recipes = [];
        if (isset($key) && $key == 'Y') {
            $idd = explode("-", $id);
            if ($idd[1] == 'p')
                $recipes = RecipeProduct::with('unitTransfer')->where([['product_id', '=', $idd[0]]])->get();
            else
                $recipes = RecipeModifier::with('unitTransfer')->where([['modifier_id', '=', $idd[0]]])->get();
            $resRecipes = [];
            foreach ($recipes as $recipe) {
                $newItem = $recipe->toArray();
                if ($recipe->item_type == 'p') {
                    $newItem["product_id"] = $recipe->item_id . '-p';
                    $prod = $recipe->products->toArray();
                    $prod["id"] =  $recipe->item_id . '-p';
                    if (isset($recipe->products))
                        $newItem["products"] = $prod;
                }
                if ($recipe->item_type == 'i') {
                    $newItem["product_id"] = $recipe->item_id . '-i';
                    $ingr = $recipe->ingredients->toArray();
                    $ingr["id"] =  $recipe->item_id . '-i';
                    $newItem["products"] = $ingr;
                }
                $resRecipes[] = $newItem;
            }
        } else {
            $resRecipes = RecipeProduct::where([['product_id', '=', $id]])->get();
            foreach ($resRecipes as $rec) {
                $rec->newid = $rec->item_id . "-" . $rec->item_type;
            }
        }
        return response()->json($resRecipes);
    }

    public function listPrepRecipe(Request $request)
    {
        $resRecipes = [];
        $modRecipes = [];
        $prodRecipes = [];
        $prodIds = [];
        $modIds = [];
        $groupedData = collect($request['data'])
            ->groupBy('item_id')
            ->flatMap(function ($items) {
                $sumTimes = $items->sum('times');
                // Modify the first item to store the summed quantity
                $first = $items->first();
                $first['times'] = $sumTimes;
                return [$first];
            });
        $timesMap = collect($groupedData)->pluck('times', 'item_id');
        foreach ($groupedData as $data) {
            $idd = explode("-", $data['item_id']);
            $data['type'] = $idd[1];
            if ($idd[1] == 'p')
                $prodIds[] = $idd[0];
            else
                $modIds[] = $idd[0];
        }
        $prodRecipes = RecipeProduct::with(['products', 'unitTransfer'])->whereIn('product_id', $prodIds)->get();
        $prodRecipes->each(function ($product) use ($timesMap) {
            $product->quantity = $product->quantity * ($timesMap[$product->product_id . '-p'] ?? 1);
        });
        $modRecipes = RecipeModifier::with(['products', 'unitTransfer'])->whereIn('modifier_id', $modIds)->get();
        $modRecipes->each(function ($modifier) use ($timesMap) {
            $modifier->quantity = $modifier->quantity * ($timesMap[$modifier->modifier_id . '-m'] ?? 1);
        });
        $result = array_merge($prodRecipes->toArray(), $modRecipes->toArray());
        $groupedResult = collect($result)
            ->groupBy('item_id')
            ->flatMap(function ($items) {
                $sumQuantity = $items->sum('quantity');
                // Modify the first item to store the summed quantity
                $first = $items->first();
                $first['quantity'] = $sumQuantity;
                return [$first];
            });
        foreach ($groupedResult as $newItem) {
            $newItem["product_id"] = $newItem["item_id"] . '-p';
            $prod = $newItem["products"];
            $prod["id"] =  $newItem["item_id"] . '-p';
            $newItem["products"] = $prod;
            $resRecipes[] = $newItem;
        }
        return response()->json($resRecipes);
    }

    public function index()
    {
        return view('product::product.index');
    }

    public function barcode()
    {
        return view('product::product.barcode');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $product  = new Product();
        $priceTier = PriceTier::get();
        $establishments = Establishment::where('is_main', 0)->get();
        $product->group_combo = 0;
        $product->linked_combo = 0;
        $product->set_price = 0;
        $product->use_upcharge = 0;
        $product->combos = [];
        $product->linkedCombos = [];
        $product->establishments = $establishments;
        $product->price_tiers = [];
        $product->recipe = [];
        $product->attributes = [];
        //$product->taxIds = [];
        $product->active = 1;
        $product->for_sell = 1;
        $product->show_in_menu = 0;
        return view('product::product.create', compact('product'));
    }

    private function validateInUse($product_id)
    {
        $product = TransactionSellLine::where([['product_id', '=', $product_id]])->first();
        if ($product != null)
            return response()->json(["message" => "PRODUCT_USED_INVENTORY"]);
        $product = TransactionePurchasesLine::where([['product_id', '=', $product_id]])->first();
        if ($product != null)
            return response()->json(["message" => "PRODUCT_USED_INVENTORY"]);
        $product = ProductComboItem::where([['item_id', '=', $product_id]])->first();
        if ($product != null)
            return response()->json(["message" => "PRODUCT_USED_COMBO"]);
        $product = RecipeProduct::where([
            ['item_id', '=', $product_id],
            ['item_type', '=', 'p']
        ])->first();
        if ($product != null)
            return response()->json(["message" => "PRODUCT_USED_RECIPE"]);
        $product = Prep::where([['product_id', '=', $product_id]])->first();
        if ($product != null)
            return response()->json(["message" => "PRODUCT_USED_PREP"]);
        return null;
    }

    public function validateProduct($id, $product)
    {
        $checkResult = [];
        $uniqueFields = ['name_ar', 'name_en'];
        if (isset($product['SKU']))
            $uniqueFields[] = 'SKU';
        if ($id != null)
            $query = Product::where('id', '!=', $id);
        else
            $query = Product::whereRaw('1 = 1');
        $query = $query->where(function ($subQuery) use ($uniqueFields, $product) {
            for ($i = 0; $i < count($uniqueFields); $i++) {
                $subQuery = $subQuery->orWhere($uniqueFields[$i], '=', $product[$uniqueFields[$i]]);
            }
        });
        $products = $query->get();
        for ($i = 0; $i < count($uniqueFields); $i++) {
            $res = array_filter($products->toArray(), function ($prod) use ($product, $uniqueFields, $i) {
                return $prod[$uniqueFields[$i]] == $product[$uniqueFields[$i]]; // Keep only even numbers
            });
            if (count($res) > 0)
                $checkResult[] = $uniqueFields[$i];
        }
        if (count($checkResult) > 0) {
            return [
                'message' => 'UNIQUE',
                'data' => $checkResult
            ];
        }
        return $checkResult;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        error_log(json_encode($request->all()));
        $validated = $request->validate($this->requetsValidator);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $validateUsing = $this->validateInUse($validated['id']);
            if ($validateUsing != null)
                return $validateUsing;
            $product = Product::find($validated['id']);
            $product->delete();
            return response()->json(["message" => "Done"]);
        } else if (isset($validated['id'])) {
            $res = $this->validateProduct($validated['id'], $validated);
            if (count($res) > 0)
                return $res;
            $this->saveProduct($validated, $request);
        } else {
            $res = $this->validateProduct(null, $validated);
            if (count($res) > 0)
                return $res;
            $this->createProduct($validated, $request);
        }
        return response()->json(["message" => "Done"]);
    }

    protected function saveProduct($validated, $request)
    {
        $image = null;
        $product = Product::find($validated['id']);
        $product->fill($validated);
        // $product->name_ar = $validated['name_ar'];
        // $product->name_en = $validated['name_en'];
        // $product->description_ar = isset($validated['description_ar'])? $validated['description_ar'] :"";
        // $product->description_en = isset($validated['description_en'])? $validated['description_en']:"";
        // $product->SKU = isset($validated['SKU'])? $validated['SKU'] :  $product->SKU;
        // $product->barcode =isset($validated['barcode'])? $validated['barcode']: $product->barcode;
        // $product->category_id = $validated['category_id'];
        // $product->tax_id = $validated['tax_id'];
        // $product->subcategory_id = $validated['subcategory_id'];
        // $product->active = $validated['active'];
        // $product->order = $validated['order'] ?? null;
        // $product->for_sell = $validated['for_sell'];
        // $product->sold_by_weight = $validated['sold_by_weight'];
        // $product->track_serial_number = $validated['track_serial_number'];
        // $product->commissions = isset($validated['commissions'])? $validated['commissions']: $product->commissions;
        // $product->price = $validated['price'];
        // $product->cost = $validated['cost'];
        // $product->color = isset($validated['color'])?$validated['color']: $product->color ;
        // $product->prep_recipe = isset($validated['prep_recipe'])? $validated['prep_recipe']: $product->prep_recipe;
        // $product->recipe_yield = isset($validated['recipe_yield'])? $validated['recipe_yield']: $product->recipe_yield;
        // $product->group_combo = $validated['group_combo'] ?? 0;
        // $product->set_price = isset($validated['set_price'])? $validated['set_price'] : null;
        // $product->use_upcharge = isset($validated['use_upcharge']) ?$validated['use_upcharge'] : null;
        // $product->linked_combo = $validated['linked_combo'] ?? 0;
        // $product->promot_upsell = isset($validated['promot_upsell']) ?$validated['promot_upsell'] : null ;
        if (isset($request["image_deleted"])) {
            $filePath = public_path($product->image);
            if (File::exists($product->image))
                File::delete($product->image);
            $product->image = null;
        }
        if ($request->hasFile('image_file')) {
            $filePath = public_path($product->image);
            if (File::exists($product->image))
                File::delete($product->image);
            $tenant = tenancy()->tenant;
            $tenantId = $tenant->id;
            // Get the uploaded file
            $file = $request->file('image_file');

            // Define the path based on the tenant's ID
            $filePath =  '/product/images';

            // Store the file
            $fileExtension = $file->getClientOriginalExtension();
            $file->storeAs($filePath, $product->id . '.' . $fileExtension, 'public'); // Store in public disk

            // Optionally save the file path to the database
            $product->image = 'storage/' . 'tenant' . $tenantId  . $filePath . '/' . $product->id . '.' . $fileExtension;
            $image = $product->image;
        }
        DB::transaction(function () use ($product, $request, $image) {
            $product->save();
            if (isset($request["modifiers"])) {
                $this->saveModifiers($request["modifiers"], $product->id);
            }
            $oldRecipe = RecipeProduct::where('product_id', $product->id)->get();
            foreach ($oldRecipe as $recipe) {
                $recipe->delete();
            }
            if (isset($request["recipe"])) {
                $order = 0;
                foreach ($request["recipe"] as $recipe) {
                    $rec = [];
                    $rec['product_id'] =  $product->id;
                    $rec['quantity'] = $recipe['quantity'];
                    $newid = $recipe['newid'];
                    if (!str_contains($newid, '-')) {
                        $newid = preg_replace('/(\d+)([a-zA-Z]+)/', '$1-$2', $newid);
                    }
                    $recipeIngredient = explode("-", $newid);

                    Log::info("Request data recipe:", $recipeIngredient);
                    $rec['item_id'] = $recipeIngredient[0];
                    $rec['item_type'] = $recipeIngredient[1];
                    $rec["unit_transfer_id"] = $recipe["unit_transfer"]["id"];
                    $rec['order'] =  $order++;
                    RecipeProduct::create($rec);
                }
            }

            $oldAttributes = Product::where('parent_id', $product->id)->get();
            foreach ($oldAttributes as $oldAttribute) {
                $oldAttribute->delete();
            }
            if (isset($request["attributes"])) {
                $product->type = "variable";
                $product->for_sell = 0;
                $product->save();
                foreach ($request["attributes"] as $attribute) {
                    $att = [];
                    $att['attribute_id1'] = $attribute['attribute1']['id'];
                    $att['attribute_id2'] = isset($attribute['attribute2']) ? $attribute['attribute2']['id'] : null;
                    $att['name_ar'] = $attribute['name_ar'];
                    $att['name_en'] = $attribute['name_en'];
                    $att['barcode'] = isset($attribute['barcode']) ? $attribute['barcode'] : null;
                    $att['SKU'] = isset($attribute['SKU']) ? $attribute['SKU'] : null;
                    $att['price'] = $attribute['price'];
                    $att['cost'] = $attribute['price'];
                    $att['active'] = 1;
                    $att['starting'] = isset($attribute['starting']) ? $attribute['starting'] : null;
                    $att['type'] = "product";
                    $att['parent_id'] = $product->id;
                    $att['tax_id'] = $product->tax_id;
                    $att['image'] = $image;
                    $att['category_id'] = $product->category_id;
                    $att['subcategory_id'] = $product->subcategory_id;
                    Product::create($att);
                }
                $product->category_id = null;
                $product->subcategory_id = null;
                $product->save();
            }

            $oldUnites = UnitTransfer::where('product_id', $product->id)->get();

            if (isset($request["transfer"])) {
                $ids = [];
                $insertedIds = [];
                $updatedTransfers = [];
                $requestIds = array_map(function ($item) {
                    return $item["id"];
                }, $request["transfer"]);
                UnitTransfer::where('product_id', '=',  $product->id)->whereNotIn('id', $requestIds)->delete();
                foreach ($oldUnites  as $old) {
                    $newid = [];
                    $newid['oldId'] = $old['id'];
                    $newid['newId'] = $old['id'];
                    $ids[] = $newid;
                }
                foreach ($request["transfer"] as $transfer) {
                    if ($transfer['id'] <= 0) {
                        $newid = [];
                        $inserted = [];
                        $tran = [];
                        $newid['oldId'] =  $transfer['id'];
                        $tran['product_id'] =  $product->id;
                        $tran['transfer'] = isset($transfer['transfer']) && $transfer['transfer'] != -100 ? $transfer['transfer'] : null;
                        $tran['primary'] = isset($transfer['primary']) &&  $transfer['primary'] == true ? 1 : 0;
                        $tran['unit1'] = $transfer['unit1'];
                        $tran['unit2'] = null; //$transfer['unit2'] != -100? $transfer['unit2'] : null;
                        $id = UnitTransfer::create($tran)->id;
                        $inserted['id'] = $id;
                        $inserted['unit2'] = $transfer['unit2'];
                        $newid['newId'] =  $id;
                        $ids[] = $newid;
                        $insertedIds[] = $inserted;
                    } else if (!isset($transfer['unit2'])) {
                        $updatedTransfer = UnitTransfer::find($transfer['id']);
                        $updatedTransfer['unit1'] = $transfer['unit1'];
                        $updatedTransfer->save();
                    } else {
                        $updatedTransfer = UnitTransfer::find($transfer['id']);
                        $updatedTransfer['unit1'] = $transfer['unit1'];
                        $updatedTransfer['unit2'] = $transfer['unit2'];
                        $updatedTransfer['primary'] = $transfer['primary'];
                        $updatedTransfer['transfer'] = $transfer['transfer'];
                        $updatedTransfer->save();
                    }
                }
                foreach ($insertedIds as $transfer) {
                    foreach ($ids as $updateId) {
                        if ($transfer['unit2'] == $updateId['oldId']) {
                            $updateObject = UnitTransfer::find($transfer['id']);
                            $updateObject->unit2 =  $updateId['newId'];
                            $updateObject->save();
                        }
                    }
                }
            }
            ProductCombo::where('product_id', '=', $product->id)->delete();
            if (isset($request["combos"])) {
                foreach ($request["combos"] as $combo) {
                    $productCombo = new ProductCombo();
                    $productCombo->product_id = $product->id;
                    $productCombo->name_ar = $combo["name_ar"];
                    $productCombo->name_en = $combo["name_en"];
                    $productCombo->combo_saving = $combo["combo_saving"];
                    $productCombo->quantity = $combo["quantity"];
                    $productCombo->price = isset($combo["price"]) ? $combo["price"] : null;
                    $productCombo->save();

                    if (isset($combo["products"])) {
                        ProductComboItem::where('combo_id', '=', $productCombo->id)->delete();

                        foreach ($combo["products"] as $productId) {
                            $comboItem = new ProductComboItem();
                            $comboItem->item_id = $productId;
                            $comboItem->combo_id = $productCombo->id;
                            if (isset($combo['upchargePrices'])) {
                                $index = array_search($productId, array_column($combo["upchargePrices"], 'product_id'));
                                if ($index !== false) {
                                    $comboItem->price = $combo['upchargePrices'][$index]["price"] ?? null; // تعيين السعر إذا كان موجودًا
                                }
                            }
                            $comboItem->save();
                        }
                    }
                }
            }

            if (isset($request["linkedCombos"])) {
                ProductLinkedComboItem::where('product_id', '=', $product->id)->delete();
                foreach ($request["linkedCombos"] as $linkedCombo) {
                    $productLinkedComboItem = new ProductLinkedComboItem();
                    $productLinkedComboItem->product_id = $product->id;
                    $productLinkedComboItem->linked_combo_id = $linkedCombo["linked_combo_id"];
                    $productLinkedComboItem->save();
                    if (isset($linkedCombo["upchargePrices"])) {
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
            if (isset($request["establishments"])) {
                foreach ($request["establishments"] as $newEstablishment) {

                    $establishment = new EstablishmentProduct();
                    $establishment->product_id = $product->id;
                    $establishment->establishment_id = $newEstablishment["id"];
                    $establishment->save();
                }
            }
            ProductPriceTier::where('product_id', '=', $product->id)->delete();
            if (isset($request["price_tiers"])) {
                foreach ($request["price_tiers"] as $newPriceTier) {

                    $PriceTier = new ProductPriceTier();
                    $pt = $newPriceTier['price_tier'];
                    $PriceTier->product_id = $product->id;
                    $PriceTier->price_tier_id = $pt["id"];
                    $PriceTier->price = $newPriceTier["price"];
                    $PriceTier->save();
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
        });
    }

    protected function createProduct($validated, $request)
    {
        DB::transaction(function () use ($validated, $request) {
            // 1. Create the product
            $product = Product::create($validated);

            // 5. Handle image upload if a file is provided
            if ($request->hasFile('image_file')) {
                $this->handleImageUpload($request->file('image_file'), $product);
            } else {
                $product->image = null;
            }
            // 2. Save modifiers if they exist
            if (isset($request["modifiers"])) {
                $this->saveModifiers($request["modifiers"], $product->id);
            }

            // 3. Save attributes if they exist
            if (isset($request["attributes"])) {
                $product->type = "variable";
                $product->for_sell = 0;
                $product->save();
                $this->saveAttributes($request["attributes"], $product->id, $product);
            }

            // 4. Save transfer details if they exist
            if (isset($request["transfer"])) {
                $this->saveTransferDetails($request["transfer"], $product->id);
            }

            // 6. Save recipe details if they exist
            if (isset($request["recipe"])) {
                $this->saveRecipe($request["recipe"], $product->id);
            }

            // 7. Save combos if they exist
            if (isset($request["combos"])) {
                $this->saveCombos($request["combos"], $product->id);
            }

            // 8. Save linked combos if they exist
            if (isset($request["linkedCombos"])) {
                $this->saveLinkedCombos($request["linkedCombos"], $product->id);
            }

            // 9. Save establishments if they exist
            if (isset($request["establishments"])) {
                $this->saveEstablishments($request["establishments"], $product->id);
            }

            // 10. Save price tiers if they exist
            if (isset($request["price_tiers"])) {
                $this->savePriceTiers($request["price_tiers"], $product->id);
            }

            // Optionally: Handle taxes if they exist (uncomment if needed)
            // if (isset($request["taxIds"])) {
            //     $this->saveTaxes($request["taxIds"], $product->id);
            // }
        });
    }

    // Method to save modifiers
    private function saveModifiers($modifiers, $productId)
    {
        ProductModifier::where('product_id', $productId)->delete();

        foreach ($modifiers as $modifierGroup) {
            $classData = $modifierGroup['class'];
            $mainModifier = ProductModifier::create([
                'product_id' => $productId,
                'modifier_class_id' => $classData['modifier_class_id'],
                'modifier_id' => null,
                'min_modifiers' => $classData['min_modifiers'] ?? 0,
                'max_modifiers' => $classData['max_modifiers'] ?? 0,
                'free_quantity' => $classData['free_quantity'] ?? 0,
                'free_type' => $classData['free_type'] ?? 0,
                'active' => 1,
                'default' => 0,
                'required' => 0,
                'display_order' => 0,
                'button_display' => 0,
                'modifier_display' => 0,
            ]);

            if (isset($modifierGroup['modifiers']) && is_array($modifierGroup['modifiers'])) {
                foreach ($modifierGroup['modifiers'] as $modifier) {
                    ProductModifier::create([
                        'product_id' => $productId,
                        'modifier_class_id' => $modifier['modifier_class_id'],
                        'modifier_id' => $modifier['modifier_id'],
                        'active' => 1,
                        'default' => $modifier['default'] ?? 0,
                        'required' => $modifier['required'] ?? 0,
                        'display_order' => $modifier['display_order'] ?? 0,
                        'button_display' => $modifier['button_display'] ?? 0,
                        'modifier_display' => $modifier['modifier_display'] ?? 0,
                        'free_quantity' => $modifier['free_quantity'] ?? 0,
                        'free_type' => $modifier['free_type'] ?? 0,
                        'min_modifiers' =>  0,
                        'max_modifiers' =>  0,
                    ]);
                }
            }
        }
    }

    // Method to save attributes
    private function saveAttributes($attributes, $productId, $product)
    {
        foreach ($attributes as $attribute) {
            $att = [];
            $att['attribute_id1'] = $attribute['attribute1']['id'];
            $att['attribute_id2'] = isset($attribute['attribute2']) ? $attribute['attribute2']['id'] : null;
            $att['name_ar'] = $attribute['name_ar'];
            $att['name_en'] = $attribute['name_en'];
            $att['barcode'] = isset($attribute['barcode']) ? $attribute['barcode'] : null;
            $att['SKU'] = isset($attribute['SKU']) ? $attribute['SKU'] : null;
            $att['price'] = $attribute['price'];
            $att['active'] = 1;
            $att['cost'] = $attribute['price'];
            $att['starting'] = isset($attribute['starting']) ? $attribute['starting'] : null;
            $att['type'] = "product";
            $att['tax_id'] = $product->tax_id;
            $att['parent_id'] = $productId;
            $att['image'] = $product->image;
            $att['category_id'] = $product->category_id;
            $att['subcategory_id'] = $product->subcategory_id;
            Product::create($att);
        }
        $product->category_id = null;
        $product->subcategory_id = null;
        $product->save();
    }

    // Method to save transfer details
    private function saveTransferDetails($transfers, $productId)
    {
        $ids = [];
        $insertedIds = [];

        foreach ($transfers as $transfer) {
            $tran = [
                'product_id' => $productId,
                'transfer' => $transfer['transfer'] ?? null,
                'primary' => $transfer['primary'] ?? 0,
                'unit1' => $transfer['unit1'],
                'unit2' => null,
            ];
            $newId = UnitTransfer::create($tran)->id;
            $insertedIds[] = ['id' => $newId, 'unit2' => $transfer['unit2']];
            $ids[] = ['oldId' => $transfer['id'], 'newId' => $newId];
        }

        foreach ($insertedIds as $transfer) {
            foreach ($ids as $updateId) {
                if ($transfer['unit2'] == $updateId['oldId']) {
                    $updateObject = UnitTransfer::find($transfer['id']);
                    $updateObject->unit2 = $updateId['newId'];
                    $updateObject->save();
                }
            }
        }
    }

    // Method to handle image upload
    private function handleImageUpload($file, $product)
    {
        $tenant = tenancy()->tenant;
        $tenantId = $tenant->id;
        $filePath = '/product/images';
        $fileExtension = $file->getClientOriginalExtension();
        $file->storeAs($filePath, $product->id . '.' . $fileExtension, 'public');

        // Save the file path to the database
        $product->image = 'storage/tenant' . $tenantId . $filePath . '/' . $product->id . '.' . $fileExtension;
        $product->save();
    }

    // Method to save recipe details
    private function saveRecipe($recipes, $productId)
    {
        $order = 0;
        foreach ($recipes as $recipe) {
            $newid = $recipe['newid'];
            if (!str_contains($newid, '-')) {
                $newid = preg_replace('/(\d+)([a-zA-Z]+)/', '$1-$2', $newid);
            }

            $recipeIngredient = explode("-", $newid);
            //Log::info("Request data recipe:", $recipe);
            RecipeProduct::create([
                'product_id' => $productId,
                'quantity' => $recipe['quantity'],
                'item_id' => $recipeIngredient[0],
                'item_type' => $recipeIngredient[1],
                'unit_transfer_id' => $recipe["unit_transfer"]["id"],
                'order' => $order++,
            ]);
        }
    }

    // Method to save combos
    private function saveCombos($combos, $productId)
    {
        ProductCombo::where('product_id', '=', $productId)->delete();
        foreach ($combos as $combo) {
            $productCombo = ProductCombo::create([
                'product_id' => $productId,
                'name_ar' => $combo["name_ar"] ?? '',
                'name_en' => $combo["name_en"] ?? '',
                'combo_saving' => $combo["combo_saving"] ?? 0,
                'quantity' => $combo["quantity"] ?? 1,
                'price' => $combo["price"] ?? null,
            ]);

            if (isset($combo["products"])) {
                ProductComboItem::where('combo_id', '=', $productCombo->id)->delete();

                foreach ($combo["products"] as $comboProductId) {
                    $comboItem = new ProductComboItem();
                    $comboItem->item_id = $comboProductId;
                    $comboItem->combo_id = $productCombo->id;
                    if (isset($combo['upchargePrices'])) {
                        foreach ($combo['upchargePrices'] as $upcharge) {
                            if ($upcharge['product_id'] == $comboProductId) {
                                $comboItem->price = $upcharge['price'];
                                break;
                            }
                        }
                    }

                    $comboItem->save();
                }
            }
        }
    }



    // Method to save linked combos
    private function saveLinkedCombos($linkedCombos, $productId)
    {
        ProductLinkedComboItem::where('product_id', '=', $productId)->delete();

        foreach ($linkedCombos as $linkedCombo) {
            $productLinkedComboItem = ProductLinkedComboItem::create([
                'product_id' => $productId,
                'linked_combo_id' => $linkedCombo["linked_combo_id"],
            ]);

            if (isset($linkedCombo["upchargePrices"])) {
                ProductLinkedComboUpcharge::where('product_combo_id', '=', $productLinkedComboItem->linked_combo_id)->delete();
                foreach ($linkedCombo["upchargePrices"] as $upchargePrice) {
                    ProductLinkedComboUpcharge::create([
                        'product_id' => $upchargePrice["product_id"],
                        'combo_id' => $productLinkedComboItem->linked_combo_id,
                    ]);
                }
            }
        }
    }

    // Method to save establishments
    private function saveEstablishments($establishments, $productId)
    {
        foreach ($establishments as $newEstablishment) {
            EstablishmentProduct::create([
                'product_id' => $productId,
                'establishment_id' => $newEstablishment["id"],
            ]);
        }
    }

    // Method to save price tiers
    private function savePriceTiers($priceTiers, $productId)
    {
        foreach ($priceTiers as $newPriceTier) {
            ProductPriceTier::create([
                'product_id' => $productId,
                'price_tier_id' => $newPriceTier['price_tier']['id'],
                'price' => $newPriceTier["price"],
            ]);
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
        $product = Product::with([
            'tax',
            'establishments.establishment',
            'priceTiers.priceTier',
            'recipe.unitTransfer',
            'attributes.attribute1',
            'attributes.attribute2',
            'modifiers.modifierClass',
            'modifiers.modifierItem',
            'combos.items',
            'linkedCombos.upcharges'
        ])->findOrFail($id);
        $product->allEstablishments = Establishment::where('is_main', 0)->get();
        foreach ($product->priceTiers as $rec) {
            $rec->price_with_tax = $rec->price + ($product->tax ? TaxHelper::getTax($rec->price, $product->tax->amount) : 0);
        }
        foreach ($product->recipe as $rec) {
            $rec->newid = $rec->item_id . "-" . $rec->item_type;
            $rec->cost = $rec->detail->cost;
        }
        foreach ($product->attributes as $attr) {
            if ($attr->attribute1) {
                $attr->attribute1Name_en = $attr->attribute1->name_en;
                $attr->attribute1Name_ar = $attr->attribute1->name_ar;
            } else {
                $attr->attribute1Name_en = null;
                $attr->attribute1Name_ar = null;
            }

            if ($attr->attribute2) {
                $attr->attribute2Name_en = $attr->attribute2->name_en;
                $attr->attribute2Name_ar = $attr->attribute2->name_ar;
            } else {
                $attr->attribute2Name_en = null;
                $attr->attribute2Name_ar = null;
            }
        }
        $product->combos = $product->combos;
        // $product->taxIds = array_map(function($item) {
        //     return $item["tax_id"];
        // }, $product->taxes->toArray());
        foreach ($product->combos as $d) {
            $d->products = array_map(function ($item) {
                return $item["item_id"];
            }, $d->items->toArray());
            $d->upchargePrices = array_map(function ($item) {
                $upchargePrice = [];
                $upchargePrice["product_id"] = $item["item_id"];
                $upchargePrice["price"] = $item["price"];
                return $upchargePrice;
            }, $d->items->toArray());
        }
        $product->linkedCombos = $product->linkedCombos;
        foreach ($product->linkedCombos as $d1) {
            $d1->upchargePrices = array_map(function ($item) use ($d1) {
                $upchargePrice = [];
                $upchargePrice["product_id"] = $item["product_id"];
                $upchargePrice["combo_id"] = $item["combo_id"];
                $upchargePrice["product_combo_id"] = $d1["linked_combo_id"];
                $upchargePrice["price"] = $item["price"];
                return $upchargePrice;
            }, $d1->upcharges->toArray());
        }
        $modifierGroups = $product->modifiers->groupBy('modifier_class_id')->map(function ($modifiers, $classId) use ($product) {
            $firstModifier = $modifiers->first();
            $class = optional($firstModifier)->modifierClass;

            return [
                'class' => [
                    'modifier_class_id' => $classId,
                    'product_id' => $product->id,
                    'name_ar' => $class->name_ar ?? '',
                    'name_en' => $class->name_en ?? '',
                    'min_modifiers' => $firstModifier->min_modifiers ?? 0,
                    'max_modifiers' => $firstModifier->max_modifiers ?? 0,
                    'free_quantity' => $firstModifier->free_quantity ?? 0,
                    'free_type' => $firstModifier->free_type ?? 0
                ],
                'modifiers' => $modifiers->map(function ($mod) {
                    return [
                        'id' => $mod->id,
                        'modifier_id' => $mod->modifier_id,
                        'name_ar' => optional($mod->modifierItem)->name_ar ?? '',
                        'name_en' => optional($mod->modifierItem)->name_en ?? '',
                        'active' => $mod->active,
                        'default' => $mod->default,
                        'display_order' => $mod->display_order
                    ];
                })->toArray()
            ];
        })->values()->toArray();

        return view('product::product.edit', [
            'product' => $product,
            'modifierGroups' => $modifierGroups,
        ]);
    }

    public function searchProducts(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $products = Product::where('name_ar', 'like', '%' . $key . '%')
            ->orWhere('name_en', 'like', '%' . $key . '%')
            ->take(10)
            ->get();
        $productCount = count($products);
        $modifiers = Product::where('type', 'modifier')->where('name_ar', 'like', '%' . $key . '%')
            ->orWhere('name_en', 'like', '%' . $key . '%')
            ->take($productCount > 10 ? 0 : 10 - $productCount)
            ->get();
        $productCount = count($products) + count($modifiers);
        $ingredients = Ingredient::where('name_ar', 'like', '%' . $key . '%')
            ->orWhere('name_en', 'like', '%' . $key . '%')
            ->take($productCount > 10 ? 0 : 10 - $productCount)
            ->get();
        $products = $products->map(function ($product) {
            $newProduct = $product->toArray();
            $newProduct["id"] = $product["id"] . '-p'; // Set the value of 'item_type'
            return $newProduct;
        });
        $modifiers = $modifiers->map(function ($modifier) {
            $newModifier = $modifier->toArray();
            $newModifier["id"] = $modifier["id"] . '-m'; // Set the value of 'item_type'
            return $newModifier;
        });
        $ingredients = $ingredients->map(function ($ingredient) {
            $newIngredient = $ingredient->toArray();
            $newIngredient["id"] = $ingredient["id"] . '-i'; // Set the value of 'item_type'
            return $newIngredient;
        });
        $result = array_merge($products->toArray(), $modifiers->toArray(), $ingredients->toArray());
        return response()->json($result);
    }

    public function searchPrepProducts(Request $request)
    {
        $query = $request->query('query');  // Get 'query' parameter
        $key = $request->query('key', '');
        $products = Product::where(function ($query) use ($key) {
            $query->where('name_ar', 'like', '%' . $key . '%')                // (status = 'active'
                ->orWhere('name_en', 'like', '%' . $key . '%');           // OR status = 'pending')
        })
            ->whereIn('id', function ($query) {
                $query->select('product_id')
                    ->from('product_recipe_products');
            })
            ->take(10)
            ->get();
        $productCount = count($products);
        $modifiers = Product::where('type', 'modifier')->where(function ($query) use ($key) {
            $query->where('name_ar', 'like', '%' . $key . '%')                // (status = 'active'
                ->orWhere('name_en', 'like', '%' . $key . '%');           // OR status = 'pending')
        })
            ->whereIn('id', function ($query) {
                $query->select('modifier_id')
                    ->from('product_recipe_modifiers');
            })
            ->take($productCount > 10 ? 0 : 10 - $productCount)
            ->get();

        $products = $products->map(function ($product) {
            $newProduct = $product->toArray();
            $newProduct["id"] = $product["id"] . '-p'; // Set the value of 'item_type'
            return $newProduct;
        });
        $modifiers = $modifiers->map(function ($modifier) {
            $newModifier = $modifier->toArray();
            $newModifier["id"] = $modifier["id"] . '-m'; // Set the value of 'item_type'
            return $newModifier;
        });
        $result = array_merge($products->toArray(), $modifiers->toArray());
        return response()->json($result);
    }

    public function getProductsDetails()
    {
        $products = Product::with(['unitTransfers' => function ($query) {
            $query->select('id', 'product_id', 'unit1',  'transfer');
        }])
            ->select('id', 'name_ar', 'name_en', 'sku', 'price', 'cost')
            ->get();
        $products->each(function ($product) {
            $product->unitTransfers->makeHidden(['name_ar', 'name_en']);
        });
        return response()->json($products);
    }
    public function productFastSave(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'category_id' => 'nullable|numeric',
            'subcategory_id' => 'nullable|numeric',
            'price' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'order' => 'nullable|numeric',
            'unit1' => 'nullable|string|max:255',
        ]);

        // Create product
        $lastOrder = Product::max('order') ?? 0;
        $validated['order'] = $lastOrder + 1;
        $validated['price'] = $validated['price'] ?? 0;
        $validated['cost'] = $validated['cost'] ?? 0;
        $product = Product::create($validated);

        if (isset($validated['unit1'])) {
            UnitTransfer::create([
                'unit1' => $validated['unit1'],
                'product_id' => $product->id
            ]);
        }

        return response()->json([
            'message' => 'Product saved successfully',
            'product' => $product
        ], 201);
    }
}
