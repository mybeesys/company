<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Ingredient;
use Modules\Product\Models\Product;
use Modules\Product\Models\Unit;
use Modules\Product\Models\UnitTransfer;
use Modules\Product\Models\Vendor;

class IngredientController extends Controller
{
    public function getIngredientsTree()
    {
        $ingredients = Ingredient::all();
        $treeBuilder = new TreeBuilder();
        $tree = $treeBuilder->buildTree($ingredients ,null, 'Ingredient', null, null, null);
        return response()->json($tree);
    }

    public function ingredientProductList()
    {
        $ingredients = Ingredient::all();
        $product = Product::all();
        $product = array_map(fn($item) => $item + ['type' => "-p"], $product->toArray());
        $ingredients = array_map(fn($item) => $item + ['type' => "-i"], $ingredients->toArray());
        $tree = array_merge($ingredients , $product);
        return response()->json($tree);
    }

    
    public function getUnitTypeList ()
    {
        $units = Unit::all();
        return response()->json($units);
    }

    public function getVendors ()
    {
        $units = Vendor::all();
        return response()->json($units);
    }

    public function index()
    {
        return view('product::ingredient.index' ); 
    }

    public function edit($id)
    {
        $ingredient  = Ingredient::find($id);
        return view('product::ingredient.edit', compact('ingredient'));
    }

    public function create()
    {
        $ingredient  = new Ingredient();
    
        return view('product::ingredient.create', compact('ingredient'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'cost' => 'required|numeric',
            'unit_measurement' => 'required|numeric',
            'active' => 'required|boolean',
            'SKU' => 'nullable|string',
            'barcode' => 'nullable|string',
            'vendor_id' => 'nullable|numeric',
            'reorder_point' => 'nullable|numeric',
            'reorder_quantity' => 'nullable|numeric',
            'yield_percentage' => 'nullable|numeric',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $serviceFee = Ingredient::find($validated['id']);
            $serviceFee->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $serviceFee = Ingredient::where('name_ar', $validated['name_ar'])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $serviceFee = Ingredient::where('name_en', $validated['name_en'])->first();
            if($serviceFee != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);
            
            $ingredient= Ingredient::create($validated);

            if(isset($request["transfer"]))
            {
                foreach ($request["transfer"] as $transfer) 
                {
                    $tran = [];
                    $tran['ingredient_id'] =  $ingredient->id;
                    $tran['unit2'] = $transfer['unit2'];
                    $tran['transfer'] = $transfer['transfer'];
                    $tran['primary'] = $transfer['primary'] == true? 1 : 0;
                    $tran['unit1'] = $transfer['unit1'];
                    UnitTransfer::create($tran);
                }
            }
            
        } else {
            $ingredient = Ingredient::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]])->first();
            if($ingredient != null)
                return response()->json(["message"=>"NAME_AR_EXIST"]);
            $ingredient = Ingredient::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]])->first();
            if($ingredient != null)
                return response()->json(["message"=>"NAME_EN_EXIST"]);

            $Ingredient = Ingredient::find($validated['id']);
            $Ingredient->name_ar = $validated['name_ar'];
            $Ingredient->name_en = $validated['name_en'];
            $Ingredient->cost = $validated['cost'];
            $Ingredient->unit_measurement = $validated['unit_measurement'];
            $Ingredient->active = $validated['active'];
            $Ingredient->SKU = isset($validated['SKU']) ? $validated['SKU'] : null;
            $Ingredient->barcode = isset($validated['barcode']) ? $validated['barcode'] : null;
            $Ingredient->vendor_id =  isset($validated['vendor_id']) ? $validated['vendor_id'] :null;
            $Ingredient->reorder_point = isset($validated['reorder_point']) ? $validated['reorder_point']:null;
            $Ingredient->reorder_quantity = isset($validated['reorder_quantity']) ? $validated['reorder_quantity'] :null;
            $Ingredient->yield_percentage = isset($validated['yield_percentage']) ? $validated['yield_percentage'] :null;
            if(isset($request["transfer"]))
            {
                $oldUnites = UnitTransfer::where('ingredient_id' , $validated['id'])->get();
                foreach ( $oldUnites as $oldUnite)
                {
                    $oldUnite->delete();
                }
                foreach ($request["transfer"] as $transfer) 
                {
                    $tran = [];
                    $tran['ingredient_id'] =  $validated['id'];
                    $tran['unit2'] = $transfer['unit2'];
                    $tran['transfer'] = $transfer['transfer'];
                    $tran['primary'] = $transfer['primary'] == true? 1 : 0;
                    $tran['unit1'] = $transfer['unit1'];
                    UnitTransfer::create($tran);
                }
            }
            $Ingredient->save();
        }
        return response()->json(["message" => "Done"]);
    }

   }
