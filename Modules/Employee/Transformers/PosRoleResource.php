<?php

namespace Modules\Employee\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PosRoleResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'department' => $this->department,
            'rank' => $this->rank,
            'is_active' => $this->is_active,
            'establishment_id' => $this->whenPivotLoaded('emp_employee_establishments_roles', $this->pivot?->establishment_id),
            'permissions' => PosPermissionResource::collection($this->permissions),

        ];
    }
}
