<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ModifierResource extends JsonResource
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
            'class_id' => $this->class_id,
        ];
    }
}
