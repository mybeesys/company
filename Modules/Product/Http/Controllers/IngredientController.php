<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Modules\Product\Models\Product_Attribute;
use Modules\Product\Models\ProductPriceTier;
use Modules\Product\Models\RecipeProduct;
use Modules\Product\Models\Unit;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;

class IngredientController extends Controller
{

    public function getIngredientsTree()
    {
        // $ingredients = Ingredient::all();
        $ingredients = Product::where('type', 'ingredint')->get();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($ingredients, null, 'Ingredient', null, null, null);
        return response()->json($tree);
    }

    public function ingredientProductList()
    {
        // $ingredients = Ingredient::all();
        // $product = Product::all();
        // $product = array_map(fn($item) => $item + ['type' => "-p"], $product->toArray());
        // $ingredients = array_map(fn($item) => $item + ['type' => "-i"], $ingredients->toArray());
        // $tree = array_merge($ingredients , $product);
        // return response()->json($tree);

        $product = Product::where('type', 'ingredint')->get();
        $product = array_map(fn($item) => $item + ['type' => "{$item['id']}-ingredint"], $product->toArray());
        return response()->json($product);
    }


    public function getUnitTypeList()
    {
        $units = Unit::all();
        return response()->json($units);
    }

    public function getVendors()
    {
        $units = Contact::where('business_type', 'supplier')->get();
        return response()->json($units);
    }

    public function index()
    {
        return view('product::ingredient.index');
    }

    public function edit($id)

    {
         $ingredient = Product::with([
            'establishments.establishment',
            'unitTransfers',
         ])->findOrFail($id);
        $ingredient->allEstablishments = Establishment::where('is_main', 0)->get();

        // $ingredient  = Product::with(['establishments' => function ($query) {
        //     $query->with('establishment');
        // }])->with(['unitTransfers' => function ($query) {
        //     // $query->with('unitTransfer');
        // }])->find($id);
        // return $ingredient;
        return view('product::ingredient.edit', compact('ingredient'));
    }

    public function create()
    {
        $ingredient  = new Product();
        $establishments = Establishment::where('is_main', 0)->get();
        $ingredient->establishments = $establishments;


        $ingredient->active = 1;
        return view('product::ingredient.create', compact('ingredient'));
    }

    private function validateInUse($ingredient_id)
    {
        $product = RecipeProduct::where([
            ['item_id', '=', $ingredient_id],
            ['item_type', '=', 'i']
        ])->first();
        if ($product != null)
            return response()->json(["message" => "INGREDIENT_USED_RECIPE"]);
        return null;
    }


    public function store(Request $request)
    {
        error_log(json_encode($request->all()));
        $validated = $request->all();
        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $validateUsing = $this->validateInUse($validated['id']);
            if ($validateUsing != null)
                return $validateUsing;
            $product = Product::find($validated['id']);
            $product->delete();
            return response()->json(["message" => "Done"]);
        } else if (isset($validated['id'])) {
            $validated['tax_id'] = $validated['order_tax_id'];

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

    protected function saveProduct($validated, $request)
    {
        $product = Product::find($validated['id']);
        // $validated['type'] = 'ingredint';
        $product->fill($validated);
        DB::transaction(function () use ($product, $request) {
            $product->save();

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
            EstablishmentProduct::where('product_id', '=', $product->id)->delete();
            if (isset($request["establishments"])) {
                foreach ($request["establishments"] as $newEstablishment) {

                    $establishment = new EstablishmentProduct();
                    $establishment->product_id = $product->id;
                    $establishment->establishment_id = $newEstablishment["establishment_id"];
                    $establishment->save();
                }
            }
        });
    }


    protected function createProduct($validated, $request)
    {

        DB::transaction(function () use ($validated, $request) {
            $validated['type'] = 'ingredint';

            $product = Product::create($validated);

            if (isset($request["transfer"])) {
                $ids = [];
                $insertedIds = [];
                foreach ($request["transfer"] as $transfer) {
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
            $product->save();

            if (isset($request["establishments"])) {
                foreach ($request["establishments"] as $newEstablishment) {

                    $establishment = new EstablishmentProduct();
                    $establishment->product_id = $product->id;
                    $establishment->establishment_id = $newEstablishment["id"];;
                    $establishment->save();
                }
            }
        });
    }
}
