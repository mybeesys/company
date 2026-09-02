<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\FranchiseProductCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Attribute;
use Modules\Product\Models\Modifier;
use Modules\Product\Models\Product_Attribute;
use Illuminate\Routing\Controllers\HasMiddleware;
use Modules\Product\Support\AuthorizesProductPages;
use Modules\Product\Support\ProductAccess;

class AttributeController extends Controller implements HasMiddleware
{
    use AuthorizesProductPages;

    protected static function productAuthEntity(): string
    {
        return 'attribute';
    }
    public function index()
    {
        return view('product::attribute.index');
    }

    public function getProductMatrix($id)
    {
        $product_att = Product_Attribute::where('product_id', $id)
            ->whereHas('product', function ($query) {
                $query->restrictByFranchise();
            })
            ->get();

        foreach ($product_att as $att) {
            $att->load(['attribute1', 'attribute2']);
        }

        return $product_att;
    }

    public function store(Request $request)
    {
        ProductAccess::authorizeMutation($request, 'attribute');
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'parent_id' => 'required|numeric',
            'active' => 'nullable|boolean',
            'method' => 'nullable|string',
            'cost' => 'nullable|numeric',
            'price' => 'nullable|numeric',
        ]);

        if (isset($validated['method']) && ($validated['method'] == 'delete')) {
            $attribute = Attribute::restrictByFranchise()->find($validated['id']);
            if ($attribute) {
                $attribute->delete();

                return response()->json(['message' => 'Done']);
            }

            return response()->json(['message' => 'NOT_FOUND'], 404);
        }

        if (! isset($validated['id'])) {
            $validated['order'] = Attribute::where('parent_id', $validated['parent_id'])->max('order') + 1;

            if (Attribute::where([['parent_id', '=', $validated['parent_id']], ['order', '=', $validated['order']]])->first()) {
                return response()->json(['message' => 'ORDER_EXIST']);
            }

            if (Attribute::where([['parent_id', '=', $validated['parent_id']], ['name_ar', '=', $validated['name_ar']]])->first()) {
                return response()->json(['message' => 'NAME_AR_EXIST']);
            }

            if (Attribute::where([['parent_id', '=', $validated['parent_id']], ['name_en', '=', $validated['name_en']]])->first()) {
                return response()->json(['message' => 'NAME_EN_EXIST']);
            }

            $attribute = Attribute::create($validated);
            $user = auth()->user();

            if ($user && $user->franchise_id && FranchiseProductCatalog::restrictsToGrantedProductsOnly($user)) {
                DB::table('franchise_product_permissions')->insert([
                    'franchise_id' => $user->franchise_id,
                    'permitted_type' => 'attribute',
                    'permitted_id' => $attribute->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            $attribute = Attribute::restrictByFranchise()->find($validated['id']);
            if (! $attribute) {
                return response()->json(['message' => 'NOT_FOUND'], 404);
            }

            if (Attribute::where([['id', '!=', $validated['id']], ['parent_id', '=', $validated['parent_id']], ['name_ar', '=', $validated['name_ar']]])->first()) {
                return response()->json(['message' => 'NAME_AR_EXIST']);
            }

            if (Attribute::where([['id', '!=', $validated['id']], ['parent_id', '=', $validated['parent_id']], ['name_en', '=', $validated['name_en']]])->first()) {
                return response()->json(['message' => 'NAME_EN_EXIST']);
            }

            $attribute->name_ar = $validated['name_ar'];
            $attribute->name_en = $validated['name_en'];
            $attribute->cost = $validated['cost'] ?? $attribute->cost;
            $attribute->price = $validated['price'] ?? $attribute->price;
            $attribute->active = $validated['active'];
            $attribute->save();

            $user = auth()->user();

            if ($user && $user->franchise_id && FranchiseProductCatalog::restrictsToGrantedProductsOnly($user)) {
                DB::table('franchise_product_permissions')->insert([
                    'franchise_id' => $user->franchise_id,
                    'permitted_type' => 'attribute',
                    'permitted_id' => $attribute->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Done']);
    }

    public function edit($id)
    {
        $product = Modifier::find($id);

        return view('product::product.edit', compact('product'));
    }

    public function update(Request $request, $id) {}

    public function destroy($id) {}
}
