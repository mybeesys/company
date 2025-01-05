<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\Discount\DiscountResource;

class DiscountCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => DiscountResource::collection($this->collection),
        ];
    }
}
