<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ModifierClassResource extends JsonResource
{
    public function toArray($request)
    {
        $extraData =['withProduct' => 'Y'];
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'products' => ProductModifierResource::collection($this->products->map(function ($product) use ($extraData) {
                return new ProductModifierResource($product, $extraData);
            }))
        ];
    }
}
