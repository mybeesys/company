<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Inventory\Models\ProductInventory;
use Illuminate\Http\Request;
use Modules\Establishment\Models\Establishment;
use Modules\General\Models\TransactionePurchasesLine;
use Modules\General\Models\TransactionSellLine;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\UnitTransferConvertor;

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
        $modifierInventories = [];
        if($key != null){
            $productInventories = Product::where('name_ar', 'like', '%' . $key . '%')
                                        ->orWhere('name_en', 'like', '%' . $key . '%')
                                        ->with(['inventory' => function ($query) {
                                            $query->with('vendor');
                                            $query->with('unit');
                                        }]);
            $modifierInventories = Modifier::where('name_ar', 'like', '%' . $key . '%')
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
            $modifierInventories = Modifier::with(['inventory' => function ($query) {
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
            $pp["establishment_id"] = $establishment["id"];
            $children[] = $pp;
        }
        $modifierInventories = $modifierInventories->Join('modifier_inventories', function ($join) use($establishment) {
            $join->on('modifier_inventories.modifier_id', '=', 'product_modifiers.id')
                 ->where('establishment_id', '=', $establishment["id"]); // Constant condition
        })->get();
        foreach ($modifierInventories as $modifierInventory) {
            $modifierInventory->addToFillable('inventory');
            $modifierInventory->addToFillable('qty');
            $pp = $modifierInventory->toArray();
            $pp["type"] = "modifier";
            $pp["establishment_id"] = $establishment["id"];
            $children[] = $pp;
        }
        $establishment["children"] = $children;
        return $establishment;
    }

    public function listTransactions(Request $request){
        $typ = $request->query('typ');  // Get 'query' parameter
        $id = $request->query('id', '');
        $est_id = $request->query('est', '');
        $sellLines = null;
        $purchaseLines = null;
        if($typ =='product'){
            $sellLines = TransactionSellLine::with(
                        ['product' => function($query){ $query->with('unitTransfers');}, 
                                    'unitTransfer', 
                                    'transaction'
                                    ])->where('product_id', '=', $id);
            $purchaseLines = TransactionePurchasesLine::with(
                        ['product' => function($query){ $query->with('unitTransfers');}, 
                                    'unitTransfer', 
                                    'transaction'
                                    ])->where('product_id', '=', $id);
        }
        else{
            $sellLines = TransactionSellLine::with(relations: 
                        ['modifier'=> function($query){ $query->with('unitTransfers');}, 
                                     'unitTransfer',
                                     'transaction'
                                     ])->where('modifier_id', '=', $id);
            $purchaseLines = TransactionePurchasesLine::with(
                        ['product' => function($query){ $query->with('unitTransfers');}, 
                                    'unitTransfer', 
                                    'transaction'
                                    ])->where('product_id', '=', $id);
        }
        $sellLines = $sellLines->whereHas('transaction', function ($query) use ($est_id) {
            $query->where('establishment_id', $est_id);
        })->get();
        $purchaseLines = $purchaseLines->whereHas('transaction', function ($query) use ($est_id) {
            $query->where('establishment_id', $est_id);
        })->get();
        $resultSellLine = array_map(function($item) use($typ) {
            return $this->getTransItem($item, $typ, -1);
        }, $sellLines->toArray());
        $resultPurchaseLine = array_map(function($item) use($typ, $purchaseLines) {
            return $this->getTransItem($item, $typ, 1);
        }, $purchaseLines->toArray());
        $result = array_merge($resultSellLine, $resultPurchaseLine);
        usort($result, function($a, $b) {
            return $a['transaction_date'] === $b['transaction_date']
                    ? $a['transaction_id'] <=> $b['transaction_id']
                    : $a['transaction_date'] <=> $b['transaction_date'];  // Ascending order
        });
        $updatedResult = collect($result)->map(function ($item) use (&$subtotal) {
            $subtotal += $item['signed_qty'];
            $item['sub_total'] = $subtotal;
            return $item;
        })->toArray();
        usort($updatedResult, function($a, $b) {
            return $b['transaction_date'] === $a['transaction_date']
            ? $b['transaction_id'] <=> $a['transaction_id']
            : $b['transaction_date'] <=> $a['transaction_date'];  // Descending order
        });
        return response()->json($updatedResult);
    }

    public function getTransItem($item, $typ, $sign){
        $newItem = $item;
        $newItem["type"] = $item["transaction"]["type"];
        $newItem["product"] = $typ =='product' ? $item["product"] : $item["modifier"];
        $itemType = $typ == 'product' ? 'P' : 'M';
        $newItem["transaction_date"] = $item["transaction"]["transaction_date"];
        $newItem["transaction_id"] = $item["transaction_id"];
        $units = $newItem["product"]['unit_transfers'];
        $newItem["unit_transfer"] = UnitTransferConvertor::getMainUnit($itemType, $newItem["product"]["id"], $units);
        $quantity =  UnitTransferConvertor::convertUnit($itemType, $newItem["product"]["id"], $item["unit_id"], null, 
                        $item["qyt"], $units);
        $newItem["qty"] = $quantity;
        $newItem["signed_qty"] =  $sign * $quantity;
        return $newItem;
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
        $idd = explode("-", $id);
        $result = null;
        if($idd[1] == 'p')
            $result = Product::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }])->find($idd[0]);
        if($idd[1] == 'm')
            $result = Modifier::with(['inventory' => function ($query) {
                $query->with('vendor');
                $query->with('unit');
            }])->find($idd[0]);
        return response()->json($result);
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
            if ($request["vendor"]) {
                $validated["primary_vendor_id"] = $request["vendor"]["id"];
            }
            if ($request["vendor_unit"]) {
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
