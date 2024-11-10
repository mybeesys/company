<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\LinkedCombo;
use Modules\Product\Models\ProductCombo;
use Modules\Product\Models\ProductComboItem;
use Modules\Product\Models\TreeBuilder;

class LinkedComboController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::linkedCombo.index' ); 
    }

    public function getLinkedCombos()
    {
        $TreeBuilder = new TreeBuilder();
        $linkedCombos = LinkedCombo::all();
        foreach ($linkedCombos as $linkedCombo) {
            $linkedCombo->combos = $linkedCombo->combos;
            foreach ($linkedCombo->combos as $d) {
                $d->products = array_map(function($item) {
                    return $item["item_id"];
                }, $d->items->toArray());
            }
        }
        $tree = $TreeBuilder->buildTree($linkedCombos, null, 'linkedCombo', null, null, null);
        return response()->json($tree);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'price' => 'nullable|numeric',
            'barcode' => 'nullable|string',
            'active' => 'required|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $customMenu = LinkedCombo::find($validated['id']);
            $customMenu->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            //try {
                $linkeCombo = LinkedCombo::where('name_ar', $validated['name_ar'])->first();
                if($linkeCombo != null)
                    return response()->json(["message"=>"NAME_AR_EXIST"]);
                $linkeCombo = LinkedCombo::where('name_en', $validated['name_en'])->first();
                if($linkeCombo != null)
                    return response()->json(["message"=>"NAME_EN_EXIST"]);
                DB::transaction(function () use ($validated, $request) {
                    $linkeCombo = LinkedCombo::create($validated);
                    if(isset($request["combos"]))
                    {
                        ProductCombo::where('product_id', '=', $linkeCombo->id)->delete();
                        foreach ($request["combos"] as $combo) {
                            $productCombo = new ProductCombo();
                            $productCombo->linked_combo_id = $linkeCombo->id;
                            $productCombo->name_ar = $combo["name_ar"];
                            $productCombo->name_en = $combo["name_en"];
                            $productCombo->combo_saving = 0;
                            $productCombo->quantity = $combo["quantity"];
                            $productCombo->order = $combo["order"];
                            $productCombo->save();
                            if(isset($combo["products"])){
                                ProductComboItem::where('combo_id', '=', $productCombo->id)->delete();
                                foreach ($combo["products"] as $productId) {
                                    $comboItem = new ProductComboItem();
                                    $comboItem->item_id = $productId;
                                    $comboItem->combo_id = $productCombo->id;
                                    $comboItem->save();
                                }
                            }
                        }
                    }
                    
                });
            //} catch (QueryException $e) {
            //    return response()->json(["message" => "ERROR_SAVING"]);
            //}
        } else {
            $linkeCombo = LinkedCombo::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($linkeCombo != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $linkeCombo = LinkedCombo::where([
                    ['id', '!=', $validated['id']],
                    ['name_en', '=', $validated['name_en']]])->first();
            if($linkeCombo != null)
                    return response()->json(["message"=>"NAME_EN_EXIST"]);
            $linkeCombo = LinkedCombo::find($validated['id']);
            $linkeCombo->name_ar = $validated['name_ar'];
            $linkeCombo->name_en = $validated['name_en'];
            $linkeCombo->price = $validated['price'];
            $linkeCombo->barcode = $validated['barcode'];
            $linkeCombo->active = $validated['active'];
            try {
                DB::transaction(function () use ($linkeCombo, $request) {
                    $linkeCombo->save();
                    if(isset($request["combos"]))
                    {
                        ProductCombo::where('product_id', '=', $linkeCombo->id)->delete();
                        foreach ($request["combos"] as $combo) {
                            $productCombo = new ProductCombo();
                            $productCombo->linked_combo_id = $linkeCombo->id;
                            $productCombo->name_ar = $combo["name_ar"];
                            $productCombo->name_en = $combo["name_en"];
                            $productCombo->combo_saving = 0;
                            $productCombo->quantity = $combo["quantity"];
                            $productCombo->order = $combo["order"];
                            $productCombo->save();
                            if(isset($combo["products"])){
                                ProductComboItem::where('combo_id', '=', $productCombo->id)->delete();
                                foreach ($combo["products"] as $productId) {
                                    $comboItem = new ProductComboItem();
                                    $comboItem->item_id = $productId;
                                    $comboItem->combo_id = $productCombo->id;
                                    $comboItem->save();
                                }
                            }
                        }
                    }
                    
                });
            } catch (QueryException $e) {
               return response()->json(["message" => "ERROR_SAVING"]);
            }
        }
        return response()->json(["message" => "Done"]);
    }

    public function edit($id)
    {
        $linkedCombo  = LinkedCombo::find($id);
        $linkedCombo->combos = $linkedCombo->combos;
         foreach ($linkedCombo->combos as $d) {
            $d->products = array_map(function($item) {
                return $item["item_id"];
            }, $d->items->toArray());
        }
        return view('product::linkedCombo.edit', compact('linkedCombo'));
    }

    public function create()
    {
        $linkedCombo  = new LinkedCombo();
        $linkedCombo->active = 0;
        $linkedCombo->combos = [];
        return view('product::linkedCombo.create', compact('linkedCombo'));
    }

}
?>