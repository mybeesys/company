<?php

namespace Modules\General\Transformers;

use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB as FacadesDB;
use Modules\General\Models\Country;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $subscriptions = FacadesDB::connection('mysql')->table('subscriptions')->where('subscriber_id', $this->id)->where('expired_at', '>', now())->first();
        return [
            'id' => $this->id,
            'name' => $this->name,
            // 'company_user' => User::find($this->user_id),
            'description' => $this->description,
            'ceo_name' => $this->ceo_name,
            'phone' => $this->phone,
            'zipcode' => $this->zipcode,
            'address' => 'national_address',
            'country' => Country::on('mysql')->find($this->country_id),
            'subscriptions' => $subscriptions,
            'plans' => $subscriptions ? FacadesDB::connection('mysql')->table('plans')->where('id', $subscriptions?->plan_id)->first() : [],
            'state' => $this->state,
            'city' => $this->city,
            'tax_name' => $this->tax_name,
            'logo' => $this->logo,
        ];
    }
}
