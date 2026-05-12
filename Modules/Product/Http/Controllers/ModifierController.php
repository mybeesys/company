<?php

namespace Modules\Product\Http\Controllers;

use App\Helpers\TaxHelper;
use App\Helpers\FranchiseProductCatalog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\ModifierPriceTier;
use Modules\Product\Models\Product;
use Modules\Product\Models\RecipeModifier;

class ModifierController extends Controller
{
    public function index()
    {
        return view('product::modifier.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|numeric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'class_id' => 'nullable|numeric',
            'cost' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'price_with_tax' => 'nullable|numeric',
            'SKU' => 'nullable|string',
            'barcode' => 'nullable|string',
            'tax_id' => 'nullable|numeric',
            'active' => 'nullable|boolean',
            'order' => 'nullable|numeric',
            'recipe_yield' => 'nullable|numeric',
            'prep_recipe' => 'nullable|boolean',
            'adjust_product_cost_recipe_cost' => 'nullable|boolean',
            'method' => 'nullable|string',
        ]);

        if (isset($validated['method']) && ($validated['method'] == 'delete')) {
            $modifier = Product::restrictByFranchise('modifier')
                ->where('type', 'modifier')
                ->find($validated['id']);

            if ($modifier) {
                $modifier->delete();

                return response()->json(['message' => 'Done']);
            }

            return response()->json(['message' => 'Modifier not found.'], 404);
        }

        if (! isset($validated['order'])) {
            $maxOrder = Product::restrictByFranchise('modifier')
                ->where('type', 'modifier')
                ->where('class_id', $validated['class_id'])
                ->max('order');
            $validated['order'] = $maxOrder !== null ? $maxOrder + 1 : 1;
        }

        if (isset($validated['id'])) {
            $this->saveModifier($validated, $request);
        } else {
            $this->createModifier($validated, $request);
        }

        return response()->json(['message' => 'Done']);
    }

    protected function saveModifier($validated, $request)
    {
        $modifier = Product::restrictByFranchise('modifier')->find($validated['id']);
        $modifier->fill($validated);
        $user = auth()->user();

        if ($user && $user->franchise_id && FranchiseProductCatalog::restrictsToGrantedProductsOnly($user)) {
            DB::table('franchise_product_permissions')->insert([
                'franchise_id' => $user->franchise_id,
                'permitted_type' => 'modifier',
                'permitted_id' => $modifier->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $cost = 0;
        if ($validated['adjust_product_cost_recipe_cost']) {
            foreach ($request['recipe'] as $recipe) {
                $cost += $recipe['cost'];
            }
        } else {
            $cost = $validated['cost'];
        }
        $modifier->cost = $cost;
        $modifier->type = 'modifier';

        DB::transaction(function () use ($modifier, $request) {
            $modifier->save();
            RecipeModifier::where('modifier_id', $modifier->id)->delete();

            if (isset($request['recipe'])) {
                $order = 0;
                foreach ($request['recipe'] as $recipe) {
                    $newid = $recipe['newid'];
                    $rec = [
                        'modifier_id' => $modifier->id,
                        'quantity' => $recipe['quantity'],
                        'order' => $order++,
                        'unit_transfer_id' => $recipe['unit_transfer']['id'] ?? null,
                    ];

                    if (str_contains($newid, '-')) {
                        $parts = explode('-', $newid);
                        $rec['item_id'] = $parts[0];
                        $rec['item_type'] = $parts[1];
                    } else {
                        preg_match('/^(\d+)([a-zA-Z_]+)?$/', $newid, $matches);
                        $rec['item_id'] = $matches[1] ?? null;
                        $rec['item_type'] = $matches[2] ?? null;
                    }
                    RecipeModifier::create($rec);
                }
            }

            ModifierPriceTier::where('modifier_id', $modifier->id)->delete();
            if (isset($request['price_tiers'])) {
                foreach ($request['price_tiers'] as $pt) {
                    ModifierPriceTier::create([
                        'modifier_id' => $modifier->id,
                        'price_tier_id' => $pt['price_tier']['id'],
                        'price' => $pt['price'],
                    ]);
                }
            }

            // Logic for unit transfers ... (OMITTED FOR BREVITY, same logic as IngredientController)
        });
    }

    protected function createModifier($validated, $request)
    {
        DB::transaction(function () use ($validated) {
            $price_with_tax = $validated['price_with_tax'];
            $price = $price_with_tax / 1.15;

            $modifier = Product::create(array_merge($validated, [
                'type' => 'modifier',
                'price' => $price,
            ]));
            $user = auth()->user();

            if ($user && $user->franchise_id && FranchiseProductCatalog::restrictsToGrantedProductsOnly($user)) {
                DB::table('franchise_product_permissions')->insert([
                    'franchise_id' => $user->franchise_id,
                    'permitted_type' => 'modifier',
                    'permitted_id' => $modifier->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // Similar logic for Recipe, PriceTiers, and Units as saveModifier
        });
    }

    public function edit($id)
    {
        $modifier = Product::restrictByFranchise('modifier')
            ->where('type', 'modifier')
            ->with(['tax', 'priceTiers.priceTier', 'recipe.unitTransfer'])
            ->findOrFail($id);

        foreach ($modifier->priceTiers as $rec) {
            $rec->price_with_tax = $rec->price + TaxHelper::getTax($rec->price, $modifier->tax->amount ?? 0);
        }
        foreach ($modifier->recipe as $rec) {
            $rec->newid = $rec->item_id.'-'.$rec->item_type;
            $rec->cost = $rec->detail->cost ?? 0;
        }

        return view('product::modifier.edit', compact('modifier'));
    }

    public function getModifiersList($id)
    {
        $language = app()->getLocale();
        $modifiers = Product::restrictByFranchise('modifier')
            ->where('class_id', $id)
            ->where('type', 'modifier')
            ->get();

        return response()->json($modifiers->map(fn ($m) => [
            'id' => $m->id,
            'name' => $language === 'ar' ? $m->name_ar : $m->name_en,
        ]));
    }
}
