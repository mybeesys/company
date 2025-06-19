<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Unit;
use Modules\Product\Models\UnitTransfer;

class UnitTransferController extends Controller
{
    public function getUnitsTransferList($type, $id)
    {
        if ($type == "product") {
            $prodTransfer = UnitTransfer::where('product_id', '=', $id)->get();
            return response()->json($prodTransfer);
        } else if ($type == "ingredint") {
            $ingTransfer = UnitTransfer::where('product_id', '=', $id)->get();
            return response()->json($ingTransfer);
        } else {
            $modTransfer = UnitTransfer::where('modifier_id', '=', $id)->get();
            return response()->json($modTransfer);
        }
    }

    public function getUnitTransfer($id)
    {
        $unit = UnitTransfer::where('id', $id)->first();

        return response()->json($unit);
    }
    public function searchUnitTransfers(Request $request)
    {
        $query = $request->query('query');
        $key = $request->query('key', '');

        $units = [];

        if ($request->has('id')) {
            $id = $request['id'];
            if (!str_contains($id, '-')) {
                $id = preg_replace('/(\d+)([a-zA-Z]+)/', '$1-$2', $id);
                $request['id'] = $id;
            }

            $recipeIngredient = explode("-", $id);
            if ($recipeIngredient[1] == 'p') {
                $request['product_id'] = $recipeIngredient[0];
            } else if ($recipeIngredient[1] == 'm') {;
                $request['modifier_id'] = $recipeIngredient[0];
            } else {
                $request['ingredient_id'] = intval($recipeIngredient[0]);
            }
        }

        $porduct_id = $request->query('product_id', '');
        $ingredient_id = $request->query('ingredient_id', '');
        $modifier_id = $request->query('modifier_id', '');

        if ($request->has('product_id')) {
            $units = UnitTransfer::where('unit1', 'like', '%' . $key . '%')
                ->where('product_id', '=', $porduct_id)
                ->take(10)
                ->get();
        }

        if ($request->has('ingredient_id')) {
            $units = UnitTransfer::where('unit1', 'like', '%' . $key . '%')
                ->where('product_id', '=', $ingredient_id)
                ->take(10)
                ->get();
        }

        if ($request->has('modifier_id')) {
            $units = UnitTransfer::where('unit1', 'like', '%' . $key . '%')
                ->where('modifier_id', '=', $modifier_id)
                ->take(10)
                ->get();
        }

        return response()->json($units);
    }

    public function Units($id)
    {
        $unit = Unit::find($id);
        return response()->json($unit);
    }


    public function index()
    {
        return view('product::unit.index');
    }

    public function edit($id)
    {
        $unit  = Unit::find($id);
        return view('product::unit.edit', compact('unit'));
    }

    public function create()
    {
        $ingredient  = new Unit();

        return view('product::unit.create', compact('unit'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $serviceFee = Unit::find($validated['id']);
            $serviceFee->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            $unit = Unit::where('name_ar', $validated['name_ar'])->first();
            if ($unit != null)
                return response()->json(["message" => "NAME_AR_EXIST"]);
            $unit = Unit::where('name_en', $validated['name_en'])->first();
            if ($unit != null)
                return response()->json(["message" => "NAME_EN_EXIST"]);

            Unit::create($validated);
        } else {
            $unit = Unit::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]
            ])->first();
            if ($unit != null)
                return response()->json(["message" => "NAME_AR_EXIST"]);
            $unit = Unit::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]
            ])->first();
            if ($unit != null)
                return response()->json(["message" => "NAME_EN_EXIST"]);

            $Ingredient = Unit::find($validated['id']);
            $Ingredient->name_ar = $validated['name_ar'];
            $Ingredient->name_en = $validated['name_en'];
            $Ingredient->save();
        }
        return response()->json(["message" => "Done"]);
    }
}
