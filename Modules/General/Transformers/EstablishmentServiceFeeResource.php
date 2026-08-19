<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Establishment\Models\EstablishmentServiceFee;

class EstablishmentServiceFeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EstablishmentServiceFee $fee */
        $fee = $this->resource;
        $autoApplyType = $fee->auto_apply_type !== null && $fee->auto_apply_type !== ''
            ? (string) $fee->auto_apply_type
            : '';

        return [
            'id' => (int) $fee->id,
            'name_ar' => (string) $fee->name_ar,
            'name_en' => (string) $fee->name_en,
            'amount' => (float) $fee->amount,
            'service_fee_type' => (string) ($fee->service_fee_type ?? EstablishmentServiceFee::TYPE_AMOUNT),
            'is_percent' => $fee->isPercent(),
            'application_type' => (string) ($fee->application_type ?? EstablishmentServiceFee::APPLY_ORDER),
            'applies_to' => $fee->appliesTo(),
            'calculation_method' => (string) ($fee->calculation_method ?? EstablishmentServiceFee::CALC_BEFORE_TAX),
            'calculated_on' => $fee->calculatedOn(),
            'taxable' => (bool) $fee->taxable,
            'is_active' => (bool) $fee->is_active,
            'auto_apply_type' => $autoApplyType,
            'auto_apply' => $fee->autoApplyKey(),
            'dining_type_ids' => array_values(array_map('intval', $fee->dining_type_ids ?? [])),
            'guest_count' => $fee->guest_count ? (int) $fee->guest_count : null,
            'cashier_payment_method_id' => $fee->cashier_payment_method_id
                ? (int) $fee->cashier_payment_method_id
                : null,
            'from_date' => $fee->from_date?->format('Y-m-d\TH:i:s'),
            'to_date' => $fee->to_date?->format('Y-m-d\TH:i:s'),
            'sort_order' => (int) ($fee->sort_order ?? 0),
            'fee_type_label_ar' => $fee->feeTypeLabel('ar'),
            'fee_type_label_en' => $fee->feeTypeLabel('en'),
            'application_label_ar' => $fee->applicationLabel('ar'),
            'application_label_en' => $fee->applicationLabel('en'),
            'calculation_method_label_ar' => $fee->calculationMethodLabel('ar'),
            'calculation_method_label_en' => $fee->calculationMethodLabel('en'),
            'auto_apply_label_ar' => $fee->autoApplyLabel('ar'),
            'auto_apply_label_en' => $fee->autoApplyLabel('en'),
        ];
    }
}
