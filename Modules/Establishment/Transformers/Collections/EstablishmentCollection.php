<?php

namespace Modules\Establishment\Transformers\Collections;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Establishment\Transformers\EstablishmentResource;

class EstablishmentCollection extends ResourceCollection
{
    public $collects = EstablishmentResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection
        ];
    }
}
