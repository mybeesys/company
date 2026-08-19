<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Establishment\Models\EstablishmentInternalConsumptionType;

class InternalConsumptionTypeResource extends JsonResource
{
    /**
     * Additive fields must not break existing Flutter clients.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EstablishmentInternalConsumptionType $type */
        $type = $this->resource;
        $valueType = $type->normalizedValueType();

        return [
            'id' => (int) $type->id,
            'name_ar' => (string) $type->name_ar,
            'name_en' => (string) $type->name_en,
            'value_type' => $valueType,
            'value' => $type->value !== null ? (float) $type->value : null,
            'is_active' => (bool) $type->is_active,
            'type_key' => (string) ($type->type_key ?? ''),
            'sort_order' => (int) ($type->sort_order ?? 0),
            'value_type_label_ar' => $type->valueTypeLabel('ar'),
            'value_type_label_en' => $type->valueTypeLabel('en'),
            'calculation_hint_ar' => $type->calculationHint('ar'),
            'calculation_hint_en' => $type->calculationHint('en'),
            'charge_uses' => $valueType,
            'prices_use' => 'inventory_cost',
            'allows_discount' => false,
            'allows_tax' => false,
            'allows_payments' => false,
            'allows_payment_method_fees' => false,
        ];
    }
}
