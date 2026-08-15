<?php

namespace Modules\General\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InternalConsumptionTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name_ar' => (string) $this->name_ar,
            'name_en' => (string) $this->name_en,
            'value_type' => (string) $this->value_type,
            'value' => $this->value !== null ? (float) $this->value : null,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
