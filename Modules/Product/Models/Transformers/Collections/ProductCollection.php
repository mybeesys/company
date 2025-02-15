<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\ProductResource;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        
        return [
            'data' => ProductResource::collection($this->collection),
        ];
    }
}