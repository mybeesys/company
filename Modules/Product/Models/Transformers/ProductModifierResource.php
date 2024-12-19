<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Enums\ButtonDisplay;
use Modules\Product\Enums\ModifierDisplay;
use Modules\Product\Models\ModifierClass;

class ProductModifierResource extends JsonResource
{
    public function toArray($request)
    {
        $modifierClass["name_ar"] = $this->modifiers["name_ar"];
        $modifierClass["name_en"] = $this->modifiers["name_en"];
        $modifierClass["modifiers"] = ModifierResource::collection($this->modifiers->children);
        return [
            'id' => $this->id,
            'default' => $this->default,
            'required' => $this->required,
            'free_quantity' => $this->free_quantity,
            'display_order' => $this->display_order,
            'min_modifiers' => $this->min_modifiers,
            'max_modifiers' => $this->max_modifiers,
            'button_display' => ButtonDisplay::getEnumNameByValue($this->modifier_display),
            'modifier_display' => ModifierDisplay::getEnumNameByValue($this->button_display),
            'modifier' => $modifierClass,
        ];
    }
}
