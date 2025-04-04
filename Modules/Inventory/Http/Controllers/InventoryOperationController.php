<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\General\Models\Transaction;
use Modules\Inventory\Enums\InventoryOperationStatus;
use Modules\Inventory\Models\IngredientInventoryTotal;
use Modules\Inventory\Models\InventoryOperation;
use Modules\Inventory\Models\InventoryOperationItem;
use Modules\Inventory\Models\ModifierInventoryTotal;
use Modules\Inventory\Models\ProductInventoryTotal;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\UnitTransferConvertor;

class InventoryOperationController extends Controller
{

    public function getinventoryOperations($type)
    {
        $TreeBuilder = new TreeBuilder();
        $inventoryOperations = InventoryOperation::with('establishment')->where('op_type', '=', $type)->get();
        foreach ($inventoryOperations as $inventoryOperation) {
            if($inventoryOperation->hasDetail()){
                $inventoryOperation->detail->addToFillable();
                foreach ($inventoryOperation->detail->getFillable() as $key) {
                    $inventoryOperation->$key = $inventoryOperation->detail[$key];
                    $inventoryOperation->addToFillable($key);
                }
            }
            $inventoryOperation->addToFillable('op_status_name');
            $inventoryOperation->addToFillable('establishment');
            $inventoryOperation->op_status_name = $inventoryOperation->op_status->name;
        }  
        $tree = $TreeBuilder->buildTree($inventoryOperations ,null, 'inventoryOperation', null, null, null);
        return response()->json($tree);
    }

    public function StatusUpdate(Request $request)
    {
        // Validate incoming data (optional)
        $validated = $request->validate([
            'id' => 'required|numeric',
            'status' => 'required|string',
        ]);
        $transaction = Transaction::find($validated['id']);
        $transaction->status = $validated['status'];
        DB::transaction(function () use($transaction){
            $transaction->save();
            $related = Transaction::with('establishment')->where('parent_id', $transaction->id)->first();
            if($related)
            {
                $related->status = $transaction->status;
                $related->save();
            }
        });
        $transaction->status_name = $transaction->op_status;
        return response()->json($transaction);
    }

    public function generatePoNo($opType)
    {
        $prefix = [
            'PO0' => 'PO0',
            'PREP' => 'PREP',
            'RMA' => 'RMA',
            'WASTE' => 'WASTE',
            'TRANSFER' => 'Trans'
        ];
        // Get the last invoice number (if any)
        $lastPO = InventoryOperation::where('op_type', '=', $opType)->orderBy('no', 'desc')->first();
        
        // Check if there is a previous invoice
        $newPONumber = $prefix[$opType] .'-1001';  // Default starting number
        if ($lastPO) {
            // Extract the number part from the last invoice
            preg_match('/(\d+)/', $lastPO->no, $matches);
            $lastNumber = (int)$matches[0];
            $newPONumber = $prefix[$opType] . '-' .str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }
        
        return $newPONumber;
    }

    public function isValidQty($establishment_id, $products, $ingredients, $modifiers, $times){
        $result = [];
        $prodIds =  array_map(function($product){
            return $product->product_id;
        },$products);
        $ingrIds =  array_map(function($ingredient){
        return $ingredient->ingredient_id;
        }, $ingredients);
        $modIds =  array_map(function($modifier){
            return $modifier->modifier_id;
        }, $modifiers);
        $prodTotals = ProductInventoryTotal::where('establishment_id', '=', $establishment_id)
                                            ->whereIn('product_id',$prodIds)->get();
        foreach ($products as $prod) {
            $prodTotal = array_filter($prodTotals->toArray(), function($value)use($prod) {
                return $prod->product_id == $value["product_id"]; // Keep only even numbers
            });
            $prodTotal = reset($prodTotal);
            $totalQty = isset($times) && $times!=null ? $prod->qty * $times : $prod->qty;
            $totalQty =  UnitTransferConvertor::convertUnit('P', $prod->product_id, $prod->unit_id, null, 
                            $totalQty , null);
            if((!$prodTotal) || 
                $prodTotal["qty"] == null || 
                $prodTotal["qty"] < $totalQty){
                $product = Product::find($prod->product_id);
                $result [] = [ 
                    "name_ar" => $product->name_ar ,
                    "name_en" => $product->name_en ,
                    "qty" => !$prodTotal || $prodTotal["qty"] == null ? 0 : $prodTotal["qty"]
                ];
            }
        }
        // $ingrTotals = IngredientInventoryTotal::where('establishment_id', '=', $establishment_id)
        //             ->whereIn('ingredient_id',$ingrIds)->get();
        // foreach ($ingredients as $ingr) {
        //     $ingrTotal = array_filter($ingrTotals->toArray(), function($value)use($ingr) {
        //         return $ingr->ingredient_id == $value["ingredient_id"]; // Keep only even numbers
        //     });
        //     $ingrTotal = reset($ingrTotal);
        //     $totalQty = isset($times) && $times!=null ? $ingr->qty * $times : $ingr->qty;
        //     $totalQty =  UnitTransferConvertor::convertUnit('I', $ingr->ingredient_id, $ingr->unit_id, null, 
        //                     $totalQty , null);
        //     if((!$ingrTotal) ||
        //         $ingrTotal["qty"] == null || 
        //         $ingrTotal["qty"] < $totalQty){
        //         $ingredient = Ingredient::find($ingr->ingredient_id);
        //         $result [] = [ 
        //             "name_ar" => $ingredient->name_ar ,
        //             "name_en" => $ingredient->name_en ,
        //             "qty" => $ingrTotal["qty"] == null ? 0 : $ingrTotal["qty"]
        //         ];
        //     }
        // }
        $modTotals = ModifierInventoryTotal::where('establishment_id', '=', $establishment_id)
                    ->whereIn('modifier_id', $modIds)->get();
        foreach ($modifiers as $mod) {
            $modTotal = array_filter($modTotals->toArray(), function($value)use($mod) {
                return $mod->modifier_id == $value["modifier_id"]; // Keep only even numbers
            });
            $modTotal = reset($modTotal);
            $totalQty = isset($times) && $times!=null ? $mod->qty * $times : $mod->qty;
            $totalQty =  UnitTransferConvertor::convertUnit('M', $mod->modifier_id, $mod->unit_id, null, 
                            $totalQty , null);
            if((!$modTotal) ||
                $modTotal["qty"] == null || 
                $modTotal["qty"] < $totalQty){
                $modifier = Modifier::find($mod->modifier_id);
                $result [] = [ 
                    "name_ar" => $modifier->name_ar ,
                    "name_en" => $modifier->name_en ,
                    "qty" => !$modTotal || $modTotal["qty"] == null ? 0 : $modTotal["qty"]
                ];
            }
        }
        return $result;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $type)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'op_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);
        $validated['op_type'] = $type;
        $validated['op_date'] = isset($validated['op_date']) ? $validated['op_date'] : date("Y-m-d");
        if (!isset($validated['id'])) {
            $validated["no"] = $this->generatePoNo($type);
            $validated["op_status"] = InventoryOperationStatus::new_op;
            $validated["total"] = 0;
            if(isset($request['items'])){
                $itemTotal = array_reduce($request['items'], function($carry, $item) {
                    return $carry + $item["qty"] * $item["cost"];
                }, 0);
            }
            $inventoryOperation = new InventoryOperation();
            $inventoryOperation->op_type = $validated['op_type'];
            $validated["establishment_id"] = $request["establishment"]["id"];
            $detail = $inventoryOperation->makeDetail();
            $detailValidated = null;
            if(isset($detail)){
                $detailValidated = $request->validate($detail->validated);
                $total = $itemTotal + $detail->totals($detailValidated);
                $validated["total"] = $total;
            }
            if(isset($request['items'])){
                $prods = [];
                $ingrs = [];
                $mods = [];
                foreach ($request['items'] as $newItem) {
                    $item = new InventoryOperationItem();
                    $idd = explode("-", $newItem['product']['id']);
                    $item->qty = $newItem['qty'];
                    if($idd[1] == 'p'){
                        $item->product_id = $idd[0];
                        if(isset($newItem['unit'])) 
                            $item->unit_id = $newItem['unit']['id'];
                        $prods [] = $item;
                    }
                    else if($idd[1] == 'm'){
                        $item->modifier_id = $idd[0];
                        if(isset($newItem['unit'])) 
                            $item->unit_id = $newItem['unit']['id'];
                        $mods [] = $item;
                    }
                    else{
                        $item->ingredient_id = $idd[0];
                        if(isset($newItem['unit'])) 
                            $item->unit_id = $newItem['unit']['id'];
                        $ingrs [] = $item;
                    }
                }
                if($validated['op_type']!=0){
                    $result =  $this->isValidQty($request["establishment"]["id"], $prods, $ingrs, $mods,  isset($request["times"]) ? $request["times"] : null);
                    if(count($result) >0 )
                        return response()->json($result);
                }
            }
            DB::transaction(function () use ($validated, $request, $detail, $detailValidated) {
                $inventoryOperation = InventoryOperation::create($validated);
                if(isset($detail)){
                    $detailValidated = $detail->fillValidated($detailValidated, $request);
                    $detailValidated['operation_id'] = $inventoryOperation->id;
                    $detail = $inventoryOperation->createDetail($detailValidated);
                }
                if(isset($request['items'])){
                    foreach ($request['items'] as $newItem) {
                        if(isset($newItem)){
                            $item = new InventoryOperationItem();
                            $item->operation_id = $inventoryOperation->id;
                            $idd = explode("-", $newItem['product']['id']);
                            if($idd[1] == 'p')
                                $item->product_id = $idd[0];
                            else if($idd[1] == 'm')
                                $item->modifier_id = $idd[0];
                            else
                                $item->ingredient_id = $idd[0];
                            $item->qty = $newItem['qty'];
                            $item->cost = $newItem['cost'];
                            $item->total = $newItem['qty'] * $newItem['cost'];
                            $item->item_type = $newItem['item_type'];
                            if(isset($newItem['unit'])) 
                                $item->unit_id = $newItem['unit']['id'];
                            $item->save();
                            $detailItem = $item->makeDetail($validated['op_type']);
                            if(isset($detailItem))
                            {
                                $detailItem->fillValidated($detailItem, $newItem);
                                $detailItem->operation_item_id = $item->id;
                                $detailItem = $item->createDetail($detailItem->toArray(), $validated['op_type']);
                            }
                        }
                    }
                }
            });
        }
        else {
            $inventoryOperation = InventoryOperation::find($validated['id']);
            $validated["establishment_id"] = $request["establishment"]["id"];
            $inventoryOperation->op_date = $validated["op_date"];
            $inventoryOperation->notes = $validated["notes"];
            $inventoryOperation->total = 0;
            if(isset($request['items'])){
                $itemTotal = array_reduce($request['items'], function($carry, $item) {
                    return $carry + $item["qty"] * $item["cost"];
                }, 0);
            }
            $detail = null;
            if($inventoryOperation->hasDetail())
                $inventoryOperation->detail;
            if(isset($detail)){
                $total = $itemTotal + $detail->totals($validated);
                $inventoryOperation->total = $total;

                foreach ($detail->getFillable() as $key) {
                    if(isset($validated[$key]))
                        $detail->$key = $validated[$key];
                }
                $detail = $detail->fillValidated($detail, $request);
            }
            DB::transaction(function () use ($inventoryOperation, $validated, $detail, $request) {
                $inventoryOperation->save();
                if(isset($detail))
                    $detail->save();
                if(isset($request['items'])){
                    InventoryOperationItem::where('operation_id', '=', $inventoryOperation->id)->delete();
                    foreach ($request['items'] as $newItem) {
                        if(isset($newItem)){
                            $item = new InventoryOperationItem();
                            $item->operation_id = $inventoryOperation->id;
                            $idd = explode("-", $newItem['product']['id']);
                            if($idd[1] == 'p')
                                $item->product_id = $idd[0];
                            else if($idd[1] == 'm')
                                $item->modifier_id = $idd[0];
                            else
                                $item->ingredient_id = $idd[0];
                            $item->qty = $newItem['qty'];
                            $item->cost = $newItem['cost'];
                            $item->total = $newItem['qty'] * $newItem['cost'];
                            $item->item_type = $newItem['item_type'];
                            if(isset($newItem['unit'])) 
                                $item->unit_id = $newItem['unit']['id'];
                            $item->save();
                            $detailItem = $item->makeDetail($validated['op_type']);
                            if(isset($detailItem))
                            {
                                $detailItem->fillValidated($detailItem, $newItem);
                                $detailItem->operation_item_id = $item->id;
                                $detailItem = $item->createDetail($detailItem->toArray(), $validated['op_type']);
                    
                            }
                        }
                    }
            }
            });
        }
        return response()->json(["message" => "Done"]);
    }
}
