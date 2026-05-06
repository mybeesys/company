<?php

namespace Modules\Product\Models\Transformers\Discount;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountTimeDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'parent_id' => $this->discount_time_id,
            'day_no' => $this->day_no,
            'from_time' => $this->from_time,
            'to_time' => $this->to_time,
        ];
    }
}
