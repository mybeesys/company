<?php

namespace Modules\ClientsAndSuppliers\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'currency_name_en' => $this->currency_name_en,
            'currency_symbol_en' => $this->currency_symbol_en,

        ];
    }
}
