<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\ProductModifierResource;

class ModifierCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $extraData =['withProduct' => 'N'];
        return [
            'data' => ProductModifierResource::collection($this->collection->map(function ($product) use ($extraData) {
                return new ProductModifierResource($product, $extraData);
            }))
        ];
    }
}
