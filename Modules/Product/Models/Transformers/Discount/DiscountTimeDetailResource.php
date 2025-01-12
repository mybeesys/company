<?php

namespace Modules\Product\Models\Transformers\Discount;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Lang;
use Modules\Product\Enums\DiscountFunction;
use Modules\Product\Enums\DiscountQualification;
use Modules\Product\Enums\DiscountQualificationType;
use Modules\Product\Enums\DiscountType;

class DiscountTimeDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'parent_id' => $this->discount_time_id,
            'day_no' => $this->day_no,
            'from_time' => $this->from_time,
            'to_time' => $this->to_time
        ];
    }
}