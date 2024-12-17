<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class UnitTransferResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name' => $this->unit1,
        ];
    }
}
