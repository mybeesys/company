<?php

namespace Modules\Product\Models\Transformers\ServiceFee;

use Illuminate\Http\Resources\Json\JsonResource;

class ServiceFeePaymentCardResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->paymentCard->id,
            'name_ar' => $this->paymentCard->name_ar,
            'name_en' => $this->paymentCard->name_en,
            'parent_id' => $this->service_fee_id,
        ];
    }
}