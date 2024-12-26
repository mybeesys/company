<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductInventory;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeBuilder;

class ProductInventoryController extends Controller
{

    public function getProductInventories()
    {
        $TreeBuilder = new TreeBuilder();
        $establishments = Establishment::all();
        foreach ($establishments as $establishment) {
            $productInventories = Product::with(['inventory' => function ($query) {
                    $query->with('vendor');
                    $query->with('unit');
                }])->Join('product_inventories', function ($join) use($establishment) {
                    $join->on('product_inventories.product_id', '=', 'product_products.id')
                         ->where('establishment_id', '=', $establishment->id); // Constant condition
                })
                ->get();
            $children =[];
            foreach ($productInventories as $productInventory) {
                $productInventory->addToFillable('inventory');
                $productInventory->addToFillable('qty');
                $children[] = $productInventory;
            }
            $establishment->children = $children;
        }
        $tree = $TreeBuilder->buildTree($establishments ,null, 'productInventory', null, null, null);
        return response()->json($tree);
    }
 
    public function getProductInventory($id)
    {
        $porduct = Product::with(['inventory' => function ($query) {
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
        return view('inventory::productInventory.index');
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
            'product_id' => 'required|numeric',
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
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inventory::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product  = Product::with(['inventory' => function ($query) {
            $query->with('vendor');
            $query->with('vendorUnit');
            $query->with('unit');
        }])->find($id);
        if($product->inventory == null){
            $product->inventory = new ProductInventory();
        }
        $productInventory = $product->inventory;
        $productInventory->product_id = $id;
        $productInventory->name_ar = $product->name_ar;
        $productInventory->name_en = $product->name_en;
        return view('inventory::productInventory.edit', compact('productInventory'));
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
