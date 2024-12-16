<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\CategoryResource;
use Modules\Product\Models\Transformers\ProductResource;

class CategoryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => CategoryResource::collection($this->collection),
        ];
    }
}
