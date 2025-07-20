<?php

namespace Modules\Reservation\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "code" => $this->code,
            "steating_capacity" => $this->steating_capacity,
            "table_status" => $this->table_status,
            "area_id" => $this->area_id,
            "active" => $this->active,
            "area"=> new AreaResource($this->area),
            "type" => $this->type,
        ];
    }
}
