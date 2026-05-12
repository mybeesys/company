<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\FranchiseProductCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\AttributeClass;
use Modules\Product\Models\TreeBuilder;

class AttributesClassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('product::attribute.index');
    }

    public function getAttributes()
    {
        $user = auth()->user();
        $TreeBuilder = new TreeBuilder;

        $query = AttributeClass::query();

        if ($user && $user->franchise_id && FranchiseProductCatalog::restrictsToGrantedProductsOnly($user)) {
            $query->whereHas('children', function ($q) use ($user) {
                $q->whereExists(function ($subQ) use ($user) {
                    $subQ->select(DB::raw(1))
                        ->from('franchise_product_permissions')
                        ->whereColumn('franchise_product_permissions.permitted_id', 'product_attributes.id')
                        ->where('franchise_product_permissions.permitted_type', 'attribute')
                        ->where('franchise_product_permissions.franchise_id', $user->franchise_id);
                });
            });
        }

        $attributes = $query->get();

        $tree = $TreeBuilder->buildTree($attributes, null, 'attributeClass', null, null, null);

        return response()->json($tree);
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
            'active' => 'nullable|boolean',
            'method' => 'nullable|string',
        ]);

        if (isset($validated['method']) && ($validated['method'] == 'delete')) {
            $attr = Attribute::where([['parent_id', '=', $validated['id']]])->first();
            if ($attr != null) {
                return response()->json(['message' => 'CHILD_EXIST']);
            }

            $attributeClass = AttributeClass::find($validated['id']);
            $attributeClass->delete();
        } elseif (! isset($validated['id'])) {
            $validated['order'] = AttributeClass::whereNull('deleted_at')->max('order') + 1;
            if (AttributeClass::where('order', $validated['order'])->first()) {
                return response()->json(['message' => 'ORDER_EXIST']);
            }

            if (AttributeClass::where('name_ar', $validated['name_ar'])->first()) {
                return response()->json(['message' => 'NAME_AR_EXIST']);
            }

            if (AttributeClass::where('name_en', $validated['name_en'])->first()) {
                return response()->json(['message' => 'NAME_EN_EXIST']);
            }

            AttributeClass::create($validated);
        } else {
            $attributeClass = AttributeClass::find($validated['id']);

            if (AttributeClass::where('name_ar', $validated['name_ar'])->where('id', '!=', $validated['id'])->first()) {
                return response()->json(['message' => 'NAME_AR_EXIST']);
            }

            if (AttributeClass::where('name_en', $validated['name_en'])->where('id', '!=', $validated['id'])->first()) {
                return response()->json(['message' => 'NAME_EN_EXIST']);
            }

            $attributeClass->name_ar = $validated['name_ar'];
            $attributeClass->name_en = $validated['name_en'];
            $attributeClass->active = $validated['active'];
            $attributeClass->save();
        }

        return response()->json(['message' => 'Done']);
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
        $product = AttributeClass::find($id);

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
