<?php

namespace Modules\Employee\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AdjustmentResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'adjustment_name_ar' => $this->adjustmentType->name,
            'adjustment_name_en' => $this->adjustmentType->name_en,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'type' => $this->type,
            'amount' => $this->amount,
            'amount_type' => $this->amount_type,
            'applicable_date' => $this->applicable_date,
            'apply_once' => $this->apply_once
        ];
    }
}
