<?php

namespace Modules\Establishment\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Establishment\Transformers\Collections\EstablishmentCollection;

class EstPosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'ref' => $this->ref,
            'establishment_id' => $this->establishment_id,
            'establishment' => new EstablishmentCollection($this->establishment),

        ];
    }
}
