<?php

namespace Modules\Employee\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSheetRuleResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rule_name' => $this->rule_name,
            'rule_value' => $this->rule_value
        ];
    }
}
