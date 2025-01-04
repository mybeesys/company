<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            [
                'name_en' => $this->name_en,
                'name_ar' => $this->name_ar,
                'description_en' =>  $this->description_en,
                'description_ar' =>  $this->description_ar,
                'active' => $this->active,
            ],
        ];
    }
}