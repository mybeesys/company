<?php

namespace Modules\ClientsAndSuppliers\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class bankAccountInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'bank_name'=>$this->bank_name,
            'bank_account_name'=>$this->bank_account_name,
            'country_bank'=>$this->country_bank,
            'currency'=>$this->currency,
            'iban_number'=>$this->iban_number,
            'bank_account_number'=>$this->bank_account_number,
            'swift_code'=>$this->swift_code,
            'bank_address'=>$this->bank_address,
            'bank_account_number'=>$this->bank_account_number,
            'custom_info'=>CustomInfoResource::collection($this->customInformation) ,
        ];
    }
}
