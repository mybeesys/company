<?php

namespace Modules\Sales\Services;

use App\Helpers\TaxHelper;
use Modules\General\Models\Setting;
use Modules\General\Models\TransactionSellLine;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductComboItem;

class WebSellModifiersCombosService
{
    public static function isEnabled(): bool
    {
        return (int) Setting::where('key', 'toggleSellWithModifiersCombos')->value('value') === 1;
    }

    /**
     * Lightweight flags for Select2 results.
     *
     * @return array{has_modifiers: bool, has_combos: bool}
     */
    public static function flagsForProduct(Product $product): array
    {
        return [
            'has_modifiers' => $product->modifiers()->exists(),
            'has_combos' => $product->combos()->exists(),
        ];
    }

    /**
     * Full extras payload for the selection modal.
     *
     * @return array<string, mixed>
     */
    public static function buildProductExtras(Product $product): array
    {
        $product->loadMissing([
            'tax',
            'modifiers.modifierClass.children.tax',
            'modifiers.modifierClass.children.unitTransfers',
            'modifiers.modifierItem.tax',
            'combos.items.product.tax',
            'combos.items.product.unitTransfers',
        ]);

        $locale = app()->getLocale();
        $name = $locale === 'ar' ? ($product->name_ar ?: $product->name_en) : ($product->name_en ?: $product->name_ar);
        $price = (float) ($product->price ?? 0);
        $priceWithTax = (float) ($product->price_with_tax ?? 0);
        if ($priceWithTax <= 0 && $product->tax) {
            $priceWithTax = $price + (float) TaxHelper::getTax($price, $product->tax->amount ?? 0);
        }
        if ($priceWithTax <= 0) {
            $priceWithTax = $price;
        }

        return [
            'id' => (int) $product->id,
            'name' => (string) $name,
            'name_ar' => (string) ($product->name_ar ?? ''),
            'name_en' => (string) ($product->name_en ?? ''),
            'image' => $product->image ? asset($product->image) : null,
            'price' => $price,
            'price_with_tax' => $priceWithTax,
            'has_modifiers' => $product->modifiers->isNotEmpty(),
            'has_combos' => $product->combos->isNotEmpty(),
            'modifier_groups' => self::buildModifierGroups($product),
            'combo_groups' => self::buildComboGroups($product),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildModifierGroups(Product $product): array
    {
        $groups = [];

        foreach ($product->modifiers as $link) {
            if ($link->modifierClass && $link->modifierClass->children) {
                $options = [];
                foreach ($link->modifierClass->children as $child) {
                    $options[] = self::mapModifierOption($child);
                }
                if ($options === []) {
                    continue;
                }
                $groups[] = [
                    'id' => 'class-'.$link->modifierClass->id.'-'.$link->id,
                    'name_ar' => (string) ($link->modifierClass->name_ar ?? ''),
                    'name_en' => (string) ($link->modifierClass->name_en ?? ''),
                    'required' => (bool) $link->required,
                    'min' => (int) ($link->min_modifiers ?? 0),
                    'max' => (int) ($link->max_modifiers ?? 0),
                    'options' => $options,
                ];

                continue;
            }

            if ($link->modifierItem) {
                $groups[] = [
                    'id' => 'item-'.$link->id,
                    'name_ar' => (string) ($link->modifierItem->name_ar ?? ''),
                    'name_en' => (string) ($link->modifierItem->name_en ?? ''),
                    'required' => (bool) $link->required,
                    'min' => (int) ($link->min_modifiers ?? 0),
                    'max' => max(1, (int) ($link->max_modifiers ?? 1)),
                    'options' => [self::mapModifierOption($link->modifierItem)],
                ];
            }
        }

        return $groups;
    }

    /**
     * @param  \Modules\Product\Models\Modifier|\Modules\Product\Models\Product  $item
     * @return array<string, mixed>
     */
    private static function mapModifierOption($item): array
    {
        $price = (float) ($item->price ?? 0);
        $priceWithTax = (float) ($item->price_with_tax ?? 0);
        if ($priceWithTax <= 0 && method_exists($item, 'tax') && $item->tax) {
            $priceWithTax = $price + (float) TaxHelper::getTax($price, $item->tax->amount ?? 0);
        }
        if ($priceWithTax <= 0) {
            $priceWithTax = $price;
        }

        return [
            'id' => (int) $item->id,
            'name_ar' => (string) ($item->name_ar ?? ''),
            'name_en' => (string) ($item->name_en ?? ''),
            'price' => $price,
            'price_with_tax' => $priceWithTax,
            'tax_value' => round($priceWithTax - $price, 4),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function buildComboGroups(Product $product): array
    {
        $groups = [];

        foreach ($product->combos as $combo) {
            $options = [];
            foreach ($combo->items as $item) {
                if (! $item->product) {
                    continue;
                }
                $basePrice = (float) ($item->product->price ?? 0);
                $optionPrice = (float) ($item->price ?? 0);
                $price = $optionPrice > 0 ? $optionPrice : 0.0;
                $priceWithTax = $price;
                if ($price > 0 && $item->product->tax) {
                    $priceWithTax = $price + (float) TaxHelper::getTax($price, $item->product->tax->amount ?? 0);
                }

                $options[] = [
                    'id' => (int) $item->id,
                    'item_id' => (int) $item->item_id,
                    'name_ar' => (string) ($item->product->name_ar ?? ''),
                    'name_en' => (string) ($item->product->name_en ?? ''),
                    'price' => $price,
                    'price_with_tax' => $priceWithTax,
                    'base_price' => $basePrice,
                ];
            }

            if ($options === []) {
                continue;
            }

            $groups[] = [
                'id' => (int) $combo->id,
                'name_ar' => (string) ($combo->name_ar ?? ''),
                'name_en' => (string) ($combo->name_en ?? ''),
                'quantity' => max(1, (int) ($combo->quantity ?? 1)),
                'options' => $options,
            ];
        }

        return $groups;
    }

    /**
     * Persist modifier/combo child lines under a parent sell line (POS-compatible).
     */
    public static function persistChildLines(int $transactionId, int $parentLineId, object $productPayload): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $modifiers = $productPayload->order_item_modifiers ?? [];
        if (is_string($modifiers)) {
            $modifiers = json_decode($modifiers) ?: [];
        }
        $modifiers = is_array($modifiers) ? $modifiers : (array) $modifiers;

        foreach ($modifiers as $modifier) {
            $modifier = is_array($modifier) ? (object) $modifier : $modifier;
            if (! $modifier || empty($modifier->modifier_id)) {
                continue;
            }
            TransactionSellLine::create(array_merge(
                [
                    'transaction_id' => $transactionId,
                    'parent_id' => $parentLineId,
                ],
                PosSalesInvoiceMapper::mapModifierLineAttributes($modifier)
            ));
        }

        $combos = $productPayload->order_item_combos ?? [];
        if (is_string($combos)) {
            $combos = json_decode($combos) ?: [];
        }
        $combos = is_array($combos) ? $combos : (array) $combos;

        foreach ($combos as $combo) {
            $combo = is_array($combo) ? (object) $combo : $combo;
            if (! $combo) {
                continue;
            }
            $comboItem = PosSalesInvoiceMapper::resolveComboOption($combo);
            if (! $comboItem instanceof ProductComboItem) {
                continue;
            }
            TransactionSellLine::create(array_merge(
                [
                    'transaction_id' => $transactionId,
                    'parent_id' => $parentLineId,
                ],
                PosSalesInvoiceMapper::mapComboLineAttributes($combo, $comboItem)
            ));
        }
    }

    /**
     * Serialize stored child lines for form prefill.
     *
     * @param  \Illuminate\Support\Collection<int, TransactionSellLine>  $children
     * @return array{modifiers: array<int, array<string, mixed>>, combos: array<int, array<string, mixed>>, extras_before_vat: float, extras_inc_tax: float}
     */
    public static function serializeChildrenForPrefill($children): array
    {
        $modifiers = [];
        $combos = [];
        $extrasBefore = 0.0;
        $extrasInc = 0.0;

        foreach ($children as $child) {
            $qty = max(1.0, (float) ($child->qyt ?: 1));
            if (! empty($child->modifier_id)) {
                $unitPrice = (float) $child->unit_price;
                $lineBefore = (float) ($child->total_before_vat ?? ($unitPrice * $qty));
                $lineTax = (float) ($child->tax_value ?? 0);
                // Mapper stores line total in unit_price_inc_tax for modifiers.
                $lineInc = (float) ($child->unit_price_inc_tax ?? ($lineBefore + $lineTax));
                $unitInc = $qty > 0 ? round($lineInc / $qty, 4) : $lineInc;
                $modifiers[] = [
                    'modifier_id' => (int) $child->modifier_id,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'price_with_tax' => $unitInc,
                    'discount_amount' => (float) ($child->discount_amount ?? 0),
                    'discount_type' => $child->discount_type,
                    'tax_id' => $child->tax_id,
                    'tax_value' => $lineTax,
                    'total_before_vat' => $lineBefore,
                    'name' => $child->product->name_ar ?? $child->product->name_en ?? '',
                ];
                $extrasBefore += $lineBefore;
                $extrasInc += $lineInc;
            } elseif (! empty($child->combo_id)) {
                $unitPrice = (float) $child->unit_price;
                $unitInc = (float) ($child->unit_price_inc_tax ?? $unitPrice);
                $lineBefore = (float) ($child->total_before_vat ?? ($unitPrice * $qty));
                $combos[] = [
                    'option_id' => (int) $child->combo_id,
                    'combo_group_id' => null,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'price_with_tax' => $unitInc,
                    'name' => $child->product->name_ar ?? $child->product->name_en ?? '',
                ];
                $extrasBefore += $lineBefore;
                $extrasInc += round($unitInc * $qty, 4);
            }
        }

        return [
            'modifiers' => $modifiers,
            'combos' => $combos,
            'extras_before_vat' => round($extrasBefore, 4),
            'extras_inc_tax' => round($extrasInc, 4),
        ];
    }
}
