<?php

namespace Modules\Product\Models\Transformers\Collections;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Product\Models\Transformers\AttributeResource;
use Modules\Product\Models\Transformers\ModifierClassResource;

class PAttributeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => AttributeResource::collection($this->collection),
        ];
    }
}
