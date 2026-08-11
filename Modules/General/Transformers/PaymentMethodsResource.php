<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Works for both legacy PaymentMethod and branch EstablishmentPaymentAccount rows.
     */
    public function toArray(Request $request): array
    {
        return [
            'name_en' => $this->name_en ?? $this->payment_method_key ?? null,
            'name_ar' => $this->name_ar ?? null,
            'description_en' => $this->description_en ?? null,
            'description_ar' => $this->description_ar ?? null,
            'active' => $this->active ?? 1,
            'id' => $this->id,
        ];
    }
}
