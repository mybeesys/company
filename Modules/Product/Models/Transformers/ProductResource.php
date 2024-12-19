<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $category["id"] = $this->category["id"];
        $category["name_ar"] = $this->category["name_ar"];
        $category["name_en"] = $this->category["name_en"];
        $subcategory["id"] = $this->subcategory["id"];
        $subcategory["name_ar"] = $this->subcategory["name_ar"];
        $subcategory["name_en"] = $this->subcategory["name_en"];
        return [
            'id' => $this->id,
            'type' => isset($this->combos) && count($this->combos) >0 ? 'combo' : (isset($this->attributes) && count($this->attributes) > 0 ? 'variant' : 'single'),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'SKU' => $this->SKU,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'color' => $this->color,
            'order' => $this->order,
            'price' => $this->price,
            'category' => $category,
            'subcategory' => $subcategory,
            'inventory' => isset($this->total) ? $this->total->qty : null,
            'modifiers' => ProductModifierResource::collection($this->modifiers),
            'attributes' => ProductAttributeResource::collection($this->attributes),
            'combos' => ComboResource::collection($this->combos),
            'units' => UnitTransferResource::collection($this->unitTransfers),
            'image' => isset($this->image) ? base64_encode(file_get_contents(public_path($this->image))): null
        ];
    }
}
