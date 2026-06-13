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
use Modules\Product\Models\UnitTransfer;

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
        $adjustCostFromRecipe = filter_var(
            $validated['adjust_product_cost_recipe_cost']
                ?? $request->input('adjust_product_cost_recipe_cost', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($adjustCostFromRecipe && is_array($request->input('recipe'))) {
            foreach ($request['recipe'] as $recipe) {
                $cost += (float) ($recipe['cost'] ?? 0);
            }
        } else {
            $cost = $validated['cost'] ?? $modifier->cost ?? 0;
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

            $this->saveModifierUnitTransfers($modifier->id, $request['transfer'] ?? null);
        });
    }

    protected function createModifier($validated, $request)
    {
        DB::transaction(function () use ($validated, $request) {
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

            if (isset($request['price_tiers'])) {
                foreach ($request['price_tiers'] as $pt) {
                    ModifierPriceTier::create([
                        'modifier_id' => $modifier->id,
                        'price_tier_id' => $pt['price_tier']['id'],
                        'price' => $pt['price'],
                    ]);
                }
            }

            $this->saveModifierUnitTransfers($modifier->id, $request['transfer'] ?? null);
        });
    }

    protected function saveModifierUnitTransfers(int $modifierId, ?array $transfers): void
    {
        if ($transfers === null) {
            return;
        }

        $modifierUnitScope = function ($query) use ($modifierId) {
            $query->where('product_id', $modifierId)
                ->orWhere('modifier_id', $modifierId);
        };

        $oldUnites = UnitTransfer::where($modifierUnitScope)->get();
        $ids = [];
        $insertedIds = [];
        $requestIds = array_map(fn ($item) => $item['id'], $transfers);

        UnitTransfer::where($modifierUnitScope)->whereNotIn('id', $requestIds)->delete();

        foreach ($oldUnites as $old) {
            $ids[] = ['oldId' => $old['id'], 'newId' => $old['id']];
        }

        foreach ($transfers as $transfer) {
            $transferValue = ($transfer['transfer'] ?? null) != -100 ? ($transfer['transfer'] ?? null) : null;

            if ($transfer['id'] <= 0) {
                $id = UnitTransfer::create([
                    'product_id' => $modifierId,
                    'modifier_id' => $modifierId,
                    'transfer' => $transferValue,
                    'primary' => ($transfer['primary'] ?? false) ? 1 : 0,
                    'unit1' => $transfer['unit1'],
                    'unit2' => null,
                ])->id;
                $insertedIds[] = ['id' => $id, 'unit2' => $transfer['unit2']];
                $ids[] = ['oldId' => $transfer['id'], 'newId' => $id];
            } else {
                UnitTransfer::where('id', $transfer['id'])->update([
                    'product_id' => $modifierId,
                    'modifier_id' => $modifierId,
                    'unit1' => $transfer['unit1'],
                    'unit2' => $transfer['unit2'] ?? null,
                    'primary' => $transfer['primary'] ?? 0,
                    'transfer' => $transferValue,
                ]);
            }
        }

        foreach ($insertedIds as $transfer) {
            foreach ($ids as $updateId) {
                if ($transfer['unit2'] == $updateId['oldId']) {
                    UnitTransfer::where('id', $transfer['id'])->update(['unit2' => $updateId['newId']]);
                }
            }
        }
    }

    public function edit($id)
    {
        $modifier = Product::restrictByFranchise('modifier')
            ->where('type', 'modifier')
            ->with(['tax', 'priceTiers.priceTier'])
            ->findOrFail($id);

        $recipes = RecipeModifier::where('modifier_id', $modifier->id)
            ->with(['unitTransfer'])
            ->orderBy('order')
            ->get();

        $itemCosts = Product::query()
            ->whereIn('id', $recipes->pluck('item_id')->unique()->filter())
            ->pluck('cost', 'id');

        foreach ($modifier->priceTiers as $rec) {
            $rec->price_with_tax = $rec->price + TaxHelper::getTax($rec->price, $modifier->tax->amount ?? 0);
        }

        foreach ($recipes as $rec) {
            $rec->newid = $rec->item_id.'-'.$rec->item_type;
            $itemCost = (float) ($itemCosts[$rec->item_id] ?? 0);
            $transfer = (float) ($rec->unitTransfer?->transfer ?? 0);
            $quantity = (float) ($rec->quantity ?? 0);
            $rec->cost = $transfer > 0
                ? round(($quantity / $transfer) * $itemCost, 4)
                : round($quantity * $itemCost, 4);
        }

        $modifier->setRelation('recipe', $recipes);

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
