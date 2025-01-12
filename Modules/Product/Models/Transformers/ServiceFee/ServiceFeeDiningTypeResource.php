<?php

namespace Modules\Product\Models\Transformers\ServiceFee;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceFeeDiningTypeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->diningType->id,
            'name_ar' => $this->diningType->name_ar,
            'name_en' => $this->diningType->name_en,
            'parent_id' => $this->service_fee_id,
        ];
    }
}