<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\ModifierClass;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product;
use Modules\Product\Models\TreeData;
use Modules\Product\Models\TreeObject;

class ModifierClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::modifier.index');
    }

    public function getModifiers()
    {
        $TreeBuilder = new TreeBuilder();
        $modifiers = ModifierClass::all();
        $tree = $TreeBuilder->buildTree($modifiers, null, 'modifierClass', null, null, null);
        return response()->json($tree);
    }

    public function getMiniModifierClasslist()
    {
        $result = ModifierClass::all();
        return response()->json($result);
    }

    public function getModifierClasses()
    {
        $Tree = [];
        $modifierClasses = ModifierClass::all();
        $treeId = "0";
        foreach ($modifierClasses as $item) {
            $treeObject = new TreeObject();
            $treeObject->key = $treeId;
            $treeObject->data = new TreeData();
            foreach ($item->getFillable() as $key) {
                $treeObject->data->$key = $item->$key;
            }
            $treeObject->data->id = $item->id;
            $treeObject->data->type = $item->type;
            $treeId = $treeId + 1;
            $Tree[] = $treeObject;
        }
        return response()->json($Tree);
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
            'order' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'method' => 'nullable|string'
        ]);

        if (isset($validated['method']) && $validated['method'] == "delete") {
            $childExists = Product::where('type', 'modifier')::where('class_id', $validated['id'])->exists();
            if ($childExists) {
                return response()->json(["message" => "CHILD_EXIST"], 400);
            }

            $modifierClass = ModifierClass::find($validated['id']);
            if ($modifierClass) {
                $modifierClass->delete();
                return response()->json(["message" => "Deleted successfully."]);
            }
            return response()->json(["message" => "ModifierClass not found."], 404);
        }

        if (!isset($validated['order'])) {
            $maxOrder = ModifierClass::where('active', true)
                ->max('order');
            $validated['order'] = $maxOrder !== null ? $maxOrder + 1 : 1;
        } else {
            $existingModifierClass = ModifierClass::where('order', $validated['order'])
                ->where('id', '!=', $validated['id'] ?? null)
                ->first();

            if ($existingModifierClass) {
                return response()->json(["message" => "ORDER_EXIST"], 400);
            }
        }

        $existingModifierClass = ModifierClass::where('name_ar', $validated['name_ar'])
            ->orWhere('name_en', $validated['name_en'])
            ->when(isset($validated['id']), function ($query) use ($validated) {
                return $query->where('id', '!=', $validated['id']);
            })
            ->first();

        if ($existingModifierClass) {
            if ($existingModifierClass->name_ar == $validated['name_ar']) {
                return response()->json(["message" => "NAME_AR_EXIST"], 400);
            }
            if ($existingModifierClass->name_en == $validated['name_en']) {
                return response()->json(["message" => "NAME_EN_EXIST"], 400);
            }
        }

        if (!isset($validated['id'])) {
            ModifierClass::create($validated);
        } else {
            $modifierClass = ModifierClass::find($validated['id']);
            if ($modifierClass) {
                $modifierClass->update($validated);
            } else {
                return response()->json(["message" => "ModifierClass not found."], 404);
            }
        }

        return response()->json(["message" => "Done"]);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $item = ModifierClass::find($id);

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
        $product  = ModifierClass::find($id);
        return view('product::product.edit', compact('product'));
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
