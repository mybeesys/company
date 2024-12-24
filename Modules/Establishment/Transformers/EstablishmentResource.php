<?php

namespace Modules\Establishment\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EstablishmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'is_main' => $this->is_main,
            'parent_id' => $this->parent_id,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'phone_number' => $this->contact_details,
            'logo' => $this->logo,
            'is_active' => $this->is_active
        ];
    }
}
