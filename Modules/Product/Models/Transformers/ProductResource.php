<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'color' => $this->color,
            'order' => $this->order,
            'price' => $this->price,
            'modifiers' => ProductModifierResource::collection($this->modifiers),
            //'image' => isset($this->image) ? base64_encode(file_get_contents(storage_path($this->image, 'public'))): null
        ];
    }
}
