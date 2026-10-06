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
            'price_tier_id' => $this->resolvePriceTierId(),
            'fees' => PaymentMethodFeeResource::collection($this->activeFeesForApi())->resolve(),
            'service_fees' => $this->boundEstablishmentServiceFees(),
        ];
    }

    private function resolvePriceTierId(): ?int
    {
        if (! $this->resource instanceof EstablishmentPaymentAccount) {
            return null;
        }

        $id = (int) ($this->price_tier_id ?? 0);

        return $id > 0 ? $id : null;
    }

    /**
     * @return Collection<int, mixed>
     */
    private function activeFeesForApi(): Collection
    {
        if (! (bool) config('establishment.payment_method_fees_enabled', false)) {
            return collect();
        }

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

    /**
     * Branch service fees auto-applied when this payment method is selected.
     *
     * @return list<array<string, mixed>>
     */
    private function boundEstablishmentServiceFees(): array
    {
        if (! $this->resource instanceof EstablishmentPaymentAccount) {
            return [];
        }

        $establishmentId = (int) request()->input('establishment_id');
        if ($establishmentId <= 0) {
            return [];
        }

        $rows = [];
        foreach (\Modules\Establishment\Services\EstablishmentServiceFeeResolver::feesBoundToPaymentMethod(
            $establishmentId,
            (int) $this->id
        ) as $fee) {
            $rows[] = [
                'id' => (int) ($fee['id'] ?? 0),
                'name_ar' => (string) ($fee['name_ar'] ?? ''),
                'name_en' => (string) ($fee['name_en'] ?? ''),
                'amount' => (float) ($fee['amount'] ?? 0),
                'service_fee_type' => (string) ($fee['service_fee_type'] ?? '0'),
                'is_percent' => (string) ($fee['service_fee_type'] ?? '0') === '1',
                'application_type' => (string) ($fee['application_type'] ?? '1'),
                'applies_to' => (string) ($fee['application_type'] ?? '1') === '0' ? 'item' : 'order',
                'calculation_method' => (string) ($fee['calculation_method'] ?? '0'),
                'calculated_on' => (string) ($fee['calculation_method'] ?? '0') === '1' ? 'after_tax' : 'before_tax',
                'taxable' => (bool) ($fee['taxable'] ?? false),
                'fee_direction' => (string) ($fee['fee_direction'] ?? 'COLLECTED'),
                'increases_customer_total' => (bool) ($fee['increases_customer_total'] ?? true),
                'show_on_invoice' => (bool) ($fee['show_on_invoice'] ?? true),
                'auto_apply' => 'payment_method',
                'auto_apply_type' => '2',
            ];
        }

        return $rows;
    }
}
