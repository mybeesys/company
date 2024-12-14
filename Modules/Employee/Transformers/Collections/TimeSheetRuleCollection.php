<?php

namespace Modules\Employee\Transformers\Collections;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Employee\Transformers\TimeSheetRuleResource;

class TimeSheetRuleCollection extends ResourceCollection
{

    public $collects = TimeSheetRuleResource::class;

    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection
        ];
    }
}
