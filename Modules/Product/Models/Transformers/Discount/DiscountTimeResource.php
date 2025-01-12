<?php

namespace Modules\Product\Models\Transformers\Discount;

use DateTime;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountTimeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'parent_id' => $this->discount_id,
            'from_date' => $this->from_date,
            'to_date' => $this->to_date,
            'times' => DiscountTimeDetailResource::collection($this->times)
        ];
    }
}