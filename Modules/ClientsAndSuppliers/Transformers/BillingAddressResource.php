<?php

namespace Modules\ClientsAndSuppliers\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillingAddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'street_name' => $this->street_name,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'building_number' => $this->building_number,
            'country' => $this->country,
            'custom_info' => CustomInfoResource::collection($this->customInformation),

        ];
    }
}
