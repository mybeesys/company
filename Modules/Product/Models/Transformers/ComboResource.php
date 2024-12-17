<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ComboResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'items' => GeneralResource::collection($this->items),
        ];
    }
}
