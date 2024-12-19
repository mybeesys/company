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
        $attribute2["id"] = $this->attribute2["id"];
        $attribute2["name_ar"] = $this->attribute2["name_ar"];
        $attribute2["name_en"] = $this->attribute2["name_en"];
        return [
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'price' => $this->price,
            'starting' => $this->starting,
            'attribute1' => $attribute1,
            'attribute2' => $attribute2,
        ];
    }
}
