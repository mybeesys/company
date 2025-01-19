<?php

namespace Modules\General\Transformers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\General\Models\Country;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_user' => User::find($this->user_id),
            'description' => $this->description,
            'ceo_name' => $this->ceo_name,
            'phone' => $this->phone,
            'zipcode' => $this->zipcode,
            'address' => 'national_address',
            'country' => Country::on('mysql')->find($this->country_id),
            'state' => $this->state,
            'city' => $this->city,
            'tax_name' => $this->tax_name,
            'logo' => $this->logo,
        ];
    }
}
