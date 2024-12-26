<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductInventory;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\TreeBuilder;

class IngredientInventoryController extends Controller
{

    public function getIngredientInventories()
    {
        $TreeBuilder = new TreeBuilder();
        $establishments = Establishment::all();
        foreach ($establishments as $establishment) {
            $ingredientInventories = Ingredient::with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }])->Join('ingredient_inventories', function ($join) use($establishment) {
                    $join->on('ingredient_inventories.ingredient_id', '=', 'product_ingredients.id')
                         ->where('establishment_id', '=', $establishment->id); // Constant condition
                })
                ->get();
            $children =[];
            foreach ($ingredientInventories as $ingredientInventory) {
                $ingredientInventory->addToFillable('inventory');
                $ingredientInventory->addToFillable('qty');
                $children[] = $ingredientInventory;
            }
            $establishment->children = $children;
        }
        $tree = $TreeBuilder->buildTree($establishments ,null, 'ingredientInventory', null, null, null);
        return response()->json($tree);
    }
 
    public function getIngredientInventory($id)
    {
        $porduct = Ingredient::with(['inventory' => function ($query) {
            $query->with('vendor');
            $query->with('unit');
        }])->find($id);
        return response()->json($porduct);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('inventory::ingredientInventory.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('inventory::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'threshold' => 'nullable|numeric',
            'ingredient_id' => 'required|numeric',
            'primary_vendor_default_quantity' => 'nullable|numeric',
            'primary_vendor_default_price' => 'nullable|numeric',
        ]);
        if (!isset($validated['id'])) {
            if (isset($request["unit"])) {
                $validated["unit_id"] = $request["unit"]["id"];
            }
            if (isset($request["vendor"])) {
                $validated["primary_vendor_id"] = $request["vendor"]["id"];
            }
            if (isset($request["vendor_unit"])) {
                $validated["primary_vendor_unit_id"] = $request["vendor_unit"]["id"];
            }
            ProductInventory::create($validated);
        }
        else {
            $productInventory = ProductInventory::find($validated['id']);
            $productInventory->threshold = $validated['threshold'];
            $productInventory->primary_vendor_default_quantity = $validated['primary_vendor_default_quantity'];
            $productInventory->primary_vendor_default_price = $validated['primary_vendor_default_price'];
            if (isset($request["unit"])) {
                $productInventory["unit_id"] = $request["unit"]["id"];
            }
            if (isset($request["vendor"])) {
                $productInventory["primary_vendor_id"] = $request["vendor"]["id"];
            }
            if (isset($request["vendor_unit"])) {
                $productInventory["primary_vendor_unit_id"] = $request["vendor_unit"]["id"];
            }
            $productInventory->save();
        }
        return response()->json(["message" => "Done"]);
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $ingredient  = Ingredient::with(['inventory' => function ($query) {
            $query->with('vendor');
            $query->with('vendorUnit');
            $query->with('unit');
        }])->find($id);
        if($ingredient->inventory == null){
            $ingredient->inventory = new ProductInventory();
            
        }
        $ingredientInventory = $ingredient->inventory;
        $ingredientInventory->ingredient_id = $id;
        return view('inventory::ingredientInventory.edit', compact('ingredientInventory'));
    }
}
