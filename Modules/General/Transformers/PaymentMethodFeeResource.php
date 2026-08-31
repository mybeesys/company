<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Establishment\Models\PaymentMethodFee;

class PaymentMethodFeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PaymentMethodFee $fee */
        $fee = $this->resource;
        $feeType = (string) ($fee->fee_type ?? PaymentMethodFee::FEE_TYPE_AMOUNT);
        $applicationType = (string) ($fee->application_type ?? PaymentMethodFee::APPLY_ORDER);
        $isPercent = $feeType === PaymentMethodFee::FEE_TYPE_PERCENT;
        $isItem = $applicationType === PaymentMethodFee::APPLY_ITEM;

        return [
            'id' => (int) $fee->id,
            'name_ar' => (string) ($fee->name_ar ?? ''),
            'name_en' => (string) ($fee->name_en ?? ''),
            'fee_type' => $feeType,
            'is_percent' => $isPercent,
            'amount' => (float) $fee->amount,
            'application_type' => $applicationType,
            'applies_to' => $isItem ? 'item' : 'order',
            'calculation_method' => (string) ($fee->calculation_method ?? PaymentMethodFee::CALC_BEFORE_TAX),
            'calculated_on' => $fee->calculatedOn(),
            'taxable' => (bool) ($fee->taxable ?? false),
            'is_active' => (bool) $fee->is_active,
            'sort_order' => (int) ($fee->sort_order ?? 0),
            'fee_type_label_ar' => $fee->feeTypeLabel('ar'),
            'fee_type_label_en' => $fee->feeTypeLabel('en'),
            'application_label_ar' => $fee->applicationLabel('ar'),
            'application_label_en' => $fee->applicationLabel('en'),
            'calculation_method_label_ar' => $fee->calculationMethodLabel('ar'),
            'calculation_method_label_en' => $fee->calculationMethodLabel('en'),
        ];
    }
}
