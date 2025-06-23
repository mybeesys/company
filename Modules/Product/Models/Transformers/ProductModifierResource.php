<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Enums\ButtonDisplay;
use Modules\Product\Enums\ModifierDisplay;

class ProductModifierResource extends JsonResource
{
    protected $extra;

    public function __construct($resource, $extra = null)
    {
        parent::__construct($resource);
        $this->extra = $extra;
    }

    public function toArray($request)
    {
        $modifierClass = null;
        if ($this->modifierClass) {
            $modifierClass = [
                'id' => $this->modifierClass->id,
                'name_ar' => $this->modifierClass->name_ar,
                'name_en' => $this->modifierClass->name_en,
                'modifiers' => $this->modifierClass->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name_ar' => $child->name_ar,
                        'name_en' => $child->name_en,
                        'price' => $child->price,
                        'price_with_tax' => $child->price_with_tax,
                        'product_id' => $this->product_id,
                        'modifier_id' => $this->id
                    ];
                })
            ];
        }

        $productData = [];
        if ($this->extra['withProduct'] == 'Y' && $this->products) {
            $productData = [
                'id' => $this->products->id,
                'name_ar' => $this->products->name_ar,
                'name_en' => $this->products->name_en
            ];
        }

        return [
            'id' => $this->id,
            'default' => (bool)$this->default,
            'required' => (bool)$this->required,
            'free_quantity' => $this->free_quantity,
            'display_order' => $this->display_order,
            'min_modifiers' => $this->min_modifiers,
            'max_modifiers' => $this->max_modifiers,
            'button_display' => ButtonDisplay::getEnumNameByValue($this->button_display),
            'modifier_display' => ModifierDisplay::getEnumNameByValue($this->modifier_display),
            'product_id' => $this->product_id,
            'modifier_item' => $this->modifierItem ? [
                'id' => $this->modifierItem->id,
                'name_ar' => $this->modifierItem->name_ar,
                'name_en' => $this->modifierItem->name_en,
                'price' => $this->modifierItem->price,
                'price_with_tax' => $this->modifierItem->price_with_tax
            ] : 0,
            'modifier_class' => $modifierClass,
            // 'product' => $productData
        ];
    }
}
