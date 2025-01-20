<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\TaxHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\RecipeModifier;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\ModifierPriceTier;
use Modules\Product\Models\UnitTransfer;

class ModifierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::modifier.index' ); 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'class_id' => 'nullable|numeric',
            'cost'=> 'nullable|numeric',
            'price'=> 'nullable|numeric',
            'SKU'=> 'nullable|string',
            'barcode'=> 'nullable|string',
            'tax_id' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'order' => 'nullable|numeric',
            'recipe_yield' => 'nullable|numeric',
            'prep_recipe' => 'nullable|boolean',
            'method' => 'nullable|string'
        ]);

        if(isset($validated['method']) && ($validated['method'] =="delete"))
        {
            $modifier = Modifier::find($validated['id']); 
            $modifier->delete();
            return response()->json(["message"=>"Done"]);
        }
        else if(!isset($validated['id']))
        {
            $modifier = Modifier::where([['class_id', '=', $validated['class_id']],
                                        ['order', '=', $validated['order']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $modifier = Modifier::where([['class_id', '=', $validated['class_id']],
                                        ['name_ar', '=', $validated['name_ar']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $modifier = Modifier::where([['class_id', '=', $validated['class_id']],
                                        ['name_en', '=', $validated['name_en']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $this->createModifier($validated, $request);   
        }
        else
        {
            //dd($validated['id'].' '.$validated['class_id'].' '.$validated['name_ar']);
            $modifier = Modifier::where([
                ['id', '!=', $validated['id']],
                ['class_id', '=', $validated['class_id']],
                ['order', '=', $validated['order']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"ORDER_EXIST"]);
            $modifier = Modifier::where([
                ['id', '!=', $validated['id']],
                ['class_id', '=', $validated['class_id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $modifier = Modifier::where([
                ['id', '!=', $validated['id']],
                ['class_id', '=', $validated['class_id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($modifier != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            $this->saveModifier($validated, $request);
            
        }
        return response()->json(["message"=>"Done"]);
    }

    protected function saveModifier($validated, $request){
        $modifier = Modifier::find($validated['id']);
        $modifier->name_ar  = $validated['name_ar'];
        $modifier->name_en  = $validated['name_en'];
        $modifier->class_id  = $validated['class_id'];
        $modifier->cost     = $validated['cost'];
        $modifier->price    = $validated['price'];
        $modifier->tax_id   = $validated['tax_id'];
        $modifier->active   = $validated['active'];
        $modifier->order   = $validated['order'];
        $modifier->prep_recipe = $validated['prep_recipe'] ?? $modifier->prep_recipe;
        $modifier->recipe_yield = $validated['recipe_yield'] ?? $modifier->recipe_yield;
        
        DB::transaction(function () use ($modifier, $request) {
            $modifier->save();
            $oldRecipe = RecipeModifier::where('modifier_id' , $modifier->id)->get();
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
                    $rec['modifier_id'] =  $modifier->id;
                    $rec['quantity'] = $recipe['quantity'];
                    $recipeIngredient = explode("-",$recipe['newid']);
                    $rec['item_id'] = $recipeIngredient[0];
                    $rec['item_type'] = $recipeIngredient[1];
                    $rec["unit_transfer_id"] = $recipe["unit_transfer"]["id"];
                    $rec['order'] =  $order++;
                    RecipeModifier::create($rec);
                }
            } 
            ModifierPriceTier::where('modifier_id', '=', $modifier->id)->delete();
            if(isset($request["price_tiers"]))
            {
                foreach ($request["price_tiers"] as $newPriceTier) {
                    $PriceTier = new ModifierPriceTier();
                    $pt = $newPriceTier['price_tier'];
                    $PriceTier->modifier_id = $modifier->id;
                    $PriceTier->price_tier_id = $pt["id"];
                    $PriceTier->price = $newPriceTier["price"];
                    $PriceTier->save();
                }
            }
            $oldUnites = UnitTransfer::where('modifier_id' , $modifier->id)->get();
        
            if(isset($request["transfer"])){
                $ids=[];
                $insertedIds=[];
                $updatedTransfers = [];
                $requestIds = array_map(function($item) {
                    return $item["id"];
                }, $request["transfer"]);
                UnitTransfer::where('modifier_id', '=',  $modifier->id)->whereNotIn('id', $requestIds)->delete();  
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
                        $tran['modifier_id'] =  $modifier->id;
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
        });
    }

    protected function createModifier($validated, $request){
        DB::transaction(function () use ($validated, $request) {
            $modifier= Modifier::create($validated);
            if(isset($request["recipe"]))
            {
                $order = 0 ;
                foreach ($request["recipe"] as $recipe) 
                {
                    $rec = [];
                    $rec['modifier_id'] =  $modifier->id;
                    $rec['quantity'] = $recipe['quantity'];
                    $recipeIngredient = explode("-",$recipe['newid']);
                    $rec['item_id'] = $recipeIngredient[0];
                    $rec['item_type'] = $recipeIngredient[1];
                    $rec['order'] =  $order++;
                    RecipeModifier::create($rec);
                }
            }
            if(isset($request["price_tiers"]))
            {
                foreach ($request["price_tiers"] as $newPriceTier) {
                    
                    $priceTier = new ModifierPriceTier();
                    $pt = $newPriceTier['price_tier'];
                    $priceTier->modifier_id = $modifier->id;
                    $priceTier->price_tier_id = $pt["id"];
                    $priceTier->price = $newPriceTier["price"];
                    $priceTier->save();
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
                    $tran['modifier_id'] =  $modifier->id;
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
        });
    }

    public function create()
    {
        $modifier  = new Modifier();
        $modifier->price_tiers = [];
        $modifier->recipe = [];
        $modifier->active = 1;
        return view('product::modifier.create', compact('modifier'));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = Modifier::find($id);

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
         $modifier  = Modifier::with('tax')->with(['priceTiers' => function ($query) {
            $query->with('priceTier');
        }])->with(['recipe' => function ($query) {
            $query->with('unitTransfer');
        }])->find($id);
        $modifier->price_with_tax = $modifier->price_with_tax;
        foreach ( $modifier->priceTiers as $rec) 
        {
            $rec->price_with_tax = $rec->price + TaxHelper::getTax($rec->price, $modifier->tax->amount);
        }
        foreach ( $modifier->recipe as $rec) 
        {
            $rec->newid = $rec->item_id."-".$rec->item_type;
            $rec->cost = $rec->detail->cost;
        }
        return view('product::modifier.edit', compact('modifier'));
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
