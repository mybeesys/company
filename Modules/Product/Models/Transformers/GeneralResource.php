<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class GeneralResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->product->id,
            'name_ar' => $this->product->name_ar,
            'name_en' => $this->product->name_en,
        ];
    }
}
