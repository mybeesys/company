<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ComboResource extends JsonResource
{
    public function toArray($request)
    {
        $extraData =['parent_id' => $this->id];
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'product_id' => $this->product_id,
            'items' => GeneralResource::collection($this->items->map(function ($product) use ($extraData) {
                return new GeneralResource($product, $extraData);
            })),
        ];
    }
}
