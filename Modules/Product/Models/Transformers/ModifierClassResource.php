<?php

namespace Modules\Product\Models\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ModifierClassResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
        ];
    }
}
