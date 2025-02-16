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
        if(isset($this->modifiers)){
            $modifierClass["id"] = $this->modifiers["id"];
            $modifierClass["name_ar"] = $this->modifiers["name_ar"];
            $modifierClass["name_en"] = $this->modifiers["name_en"];
        }
        if(isset($this->modifiers)){
            $modifierClass["modifiers"] = ModifierResource::collection($this->modifiers->children);
            $modifierClass["modifiers"] = ModifierResource::collection($this->modifiers->children)->collection->merge([
                'product_id' => $this->products["id"],
                'modifier_id' => $this->id
            ]);


        }
        if($this->extra['withProduct'] == 'Y' && isset($this->products)){
            $modifierClass["product"] = [];
            $modifierClass["product"]["id"] = $this->products["id"];
            $modifierClass["product"]["name_ar"] = $this->products["name_ar"];
            $modifierClass["product"]["name_en"] = $this->products["name_en"];
        }
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
            'product_id' => $this->products["id"],
            'modifier' => $modifierClass,
        ];
    }
}