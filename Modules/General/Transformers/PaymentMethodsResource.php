<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Modules\Establishment\Models\EstablishmentPaymentAccount;

class PaymentMethodsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Works for both legacy PaymentMethod and branch EstablishmentPaymentAccount rows.
     * Additive fields (`payment_method_key`, `fees`) must not break existing Flutter clients.
     *
     * @return array<string, mixed>
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
            'payment_method_key' => $this->payment_method_key ?? null,
            'fees' => PaymentMethodFeeResource::collection($this->activeFeesForApi())->resolve(),
        ];
    }

    /**
     * @return Collection<int, mixed>
     */
    private function activeFeesForApi(): Collection
    {
        if (! $this->resource instanceof EstablishmentPaymentAccount) {
            return collect();
        }

        if ($this->relationLoaded('activeFees')) {
            return $this->activeFees;
        }

        if ($this->relationLoaded('fees')) {
            return $this->fees->where('is_active', true)->values();
        }

        return $this->activeFees()->get();
    }
}
