<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\ServiceFee\ServiceFeeResource;

class ServiceFeeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => ServiceFeeResource::collection($this->collection),
        ];
    }
}
