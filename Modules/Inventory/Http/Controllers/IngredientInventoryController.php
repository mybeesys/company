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
    protected function fillIngredient($establishment, $key)
    {
        if ($establishment["is_main"] == 1) {
            $children = [];
            foreach ($establishment["children"] as $childEstablishment) {
                $est =  $this->fillIngredient($childEstablishment, $key);
                $children[] = $est;
            }
            $establishment["children"] = $children;
            return $establishment;
        }
        $ingredientInventories = [];
        if ($key != null) {
            $ingredientInventories = Ingredient::where('name_ar', 'like', '%' . $key . '%')
                ->orWhere('name_en', 'like', '%' . $key . '%')
                ->with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }]);
        } else {
            $ingredientInventories = Ingredient::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }]);
        }
        $ingredientInventories = $ingredientInventories->Join('ingredient_inventories', function ($join) use ($establishment) {
            $join->on('ingredient_inventories.ingredient_id', '=', 'product_ingredients.id')
                ->where('establishment_id', '=', $establishment["id"]); // Constant condition
        })->get();
        $children = [];
        foreach ($ingredientInventories as $ingredientInventory) {
            $ingredientInventory->addToFillable('inventory');
            $ingredientInventory->addToFillable('qty');
            $ingr = $ingredientInventory->toArray();
            $ingr["type"] = "Ingredient";
            $children[] = $ingr;
        }
        $establishment["children"] = $children;
        return $establishment;
    }

    public function getIngredientInventories(Request $request)
    {
        $by = $request->query('by');  // Get 'query' parameter
        $key = $request->query('key', '');
        $useTree = $request->query('t', '');
        $establishments = [];
        $TreeBuilder = new TreeBuilder();
        if ($by == 0) {
            $establishments = Establishment::whereNull('parent_id')->with(['children' => function ($query) use ($key) {
                $query->where('is_main', 1)
                    ->orWhere(function ($subQuery) use ($key) {
                        $subQuery->where('is_main', 0)
                            ->where('name', 'LIKE', "%{$key}%");
                    });
            }])
                ->get();
        } else {
            $establishments = Establishment::whereNull('parent_id')->with('children')->get();
        }
        $establishmentArray = $establishments->toArray();
        $details = [];
        foreach ($establishmentArray as $establishment) {
            if ($by == 1) {
                $est = $this->fillIngredient($establishment, $key);
                $details[] = $est;
            } else {
                $est = $this->fillIngredient($establishment, null);
                $details[] = $est;
            }
        }
        if (isset($useTree) && $useTree == '1') {

            return $details;
        } else {
            $tree = $TreeBuilder->buildTreeFromArray($details, null, 'ingredientInventory', null, null, null);
            return response()->json($tree);
        }
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
        error_log("flag_1");
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
        } else {
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
        if ($ingredient->inventory == null) {
            $ingredient->inventory = new ProductInventory();
        }
        $ingredientInventory = $ingredient->inventory;
        $ingredientInventory->ingredient_id = $id;
        return view('inventory::ingredientInventory.edit', compact('ingredientInventory'));
    }
}
