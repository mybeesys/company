<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
{
    public function toArray($request)
    {
        $attribute1["id"] = $this->attribute1["id"];
        $attribute1["name_ar"] = $this->attribute1["name_ar"];
        $attribute1["name_en"] = $this->attribute1["name_en"];
        $attribute1["parent_id"] = $this->id;
        $attribute2["id"] = $this->attribute2["id"];
        $attribute2["name_ar"] = $this->attribute2["name_ar"];
        $attribute2["name_en"] = $this->attribute2["name_en"];
        $attribute2["parent_id"] = $this->id;
        $product = null;
        if (isset($this->product)) {
            $product["id"] = $this->product["id"];
            $product["name_ar"] = $this->product["name_ar"];
            $product["name_en"] = $this->product["name_en"];
        }
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'price' => $this->price,
            'starting' => $this->starting,
            'attribute_items' => [$attribute1, $attribute2],
            // 'attribute2' => $attribute2,
            'product_id' => $this->product->id
        ];
    }
}