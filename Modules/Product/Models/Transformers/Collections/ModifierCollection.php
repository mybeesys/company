<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\ProductModifierResource;

class ModifierCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => ProductModifierResource::collection($this->collection),
        ];
    }
}
