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
        $feeType = (string) ($this->fee_type ?? PaymentMethodFee::FEE_TYPE_AMOUNT);
        $applicationType = (string) ($this->application_type ?? PaymentMethodFee::APPLY_ORDER);
        $isPercent = $feeType === PaymentMethodFee::FEE_TYPE_PERCENT;
        $isItem = $applicationType === PaymentMethodFee::APPLY_ITEM;

        return [
            'id' => (int) $this->id,
            'name_ar' => (string) ($this->name_ar ?? ''),
            'name_en' => (string) ($this->name_en ?? ''),
            'fee_type' => $feeType,
            'is_percent' => $isPercent,
            'amount' => (float) $this->amount,
            'application_type' => $applicationType,
            'applies_to' => $isItem ? 'item' : 'order',
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) ($this->sort_order ?? 0),
        ];
    }
}
