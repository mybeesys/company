<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Establishment\Models\Establishment;
use Modules\Product\Models\EstablishmentProduct;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeBuilder;

class ProductInventoryController extends Controller
{

    protected function fillProduct($establishment, $key){
        if($establishment["is_main"] == 1){
            $children =[];
            foreach ($establishment["children"] as $childEstablishment) {
                $est = $this->fillProduct($childEstablishment, $key);
                $children [] = $est;
            }
            $establishment["children"] = $children;
            return $establishment;
        }
        $productInventories = [];
        if($key != null){
            $productInventories = Product::where('name_ar', 'like', '%' . $key . '%')
                                        ->orWhere('name_en', 'like', '%' . $key . '%')
                                        ->with(['inventory' => function ($query) {
                                            $query->with('vendor');
                                            $query->with('unit');
                                        }]);
        }
        else{
            $productInventories = Product::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }]);
        }
        $productInventories = $productInventories->Join('product_inventories', function ($join) use($establishment) {
            $join->on('product_inventories.product_id', '=', 'product_products.id')
                 ->where('establishment_id', '=', $establishment["id"]); // Constant condition
        })->get();
        $children =[];
        foreach ($productInventories as $productInventory) {
            $productInventory->addToFillable('inventory');
            $productInventory->addToFillable('qty');
            $pp = $productInventory->toArray();
            $pp["type"] = "product";
            $children[] = $pp;
        }
        $establishment["children"] = $children;
        return $establishment;
    }

    public function getProductInventories(Request $request)
    {
        $by = $request->query('by');  // Get 'query' parameter
        $key = $request->query('key', '');
        $useTree = $request->query('t', '');
        $establishments = [];
        $TreeBuilder = new TreeBuilder();
        if($by == 0){
            $establishments = Establishment::whereNull('parent_id')->with(['children' => function ($query) use ($key) {
                $query->where('is_main', 1)
                    ->orWhere(function ($subQuery) use ($key) {
                        $subQuery->where('is_main', 0)
                                ->where('name', 'LIKE', "%{$key}%");
                    });
            }])
            ->get();
        }
        else{
            $establishments = Establishment::whereNull('parent_id')->with('children')->get();
        }
        $establishmentArray = $establishments->toArray();
        $details = [];
        foreach ($establishmentArray as $establishment) {
            if($by == 1){
                $est = $this->fillProduct($establishment, $key);
                $details [] = $est;
            } 
            else{
                $est = $this->fillProduct($establishment, null);
                $details [] = $est;
            }   
        }
        if(isset($useTree) && $useTree == '1'){
            
            return $details;
        }
        else{
            $tree = $TreeBuilder->buildTreeFromArray($details ,null, 'productInventory', null, null, null);
            return response()->json($tree);
        }
    }

    public function getِAllProductInventories()
    {
        $establishments = [];
        $establishments = Establishment::whereNull('parent_id')->with('children')->get();
        $establishmentArray = $establishments->toArray();
        $details = [];
        foreach ($establishmentArray as $establishment) {
            $est = $this->fillProduct($establishment, null);
            $details [] = $est;
        }
        return $details;
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
