<?php

namespace Modules\Employee\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'phone_number' => $this->phone_number,
            'employment_start_date' => $this->employment_start_date,
            'employment_end_date' => $this->employment_end_date,
            'establishment' => $this->defaultEstablishment?->name,
            'active' => $this->pos_is_active,
            'pin' => $this->pin,
            'ems_access' => $this->ems_access,
            'wage' => $this->wage?->rate,
            'allowances' => AdjustmentResource::collection($this->allowances),
            'deductions' => AdjustmentResource::collection($this->deductions),
            'image' => $this->image,
            'email' => $this->email,
            'posRoles' => PosRoleResource::collection($this->posRoles),
            'posPermissions' => PosPermissionResource::collection($this->permissions()->where('type', 'pos')->get()),
        ];
    }
}
