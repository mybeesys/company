<?php

namespace Modules\ClientsAndSuppliers\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'mobile_number' => $this->mobile_number,
            'phone_number' => $this->phone_number,
            // 'website' => $this->website,
            // 'point_of_sale_client' => $this->point_of_sale_client,
            // 'payment_terms' => $this->payment_terms,
            'email' => $this->email,
            // 'commercial_register' => $this->commercial_register,
            'tax_number' => $this->tax_number,
            'status' => $this->status,
            // 'bank_account_info' => new BankAccountInformationResource($this->bankAccountInformation),
            'billing_address' => new BillingAddressResource($this->billingAddress),
            // 'shipping_address' => new ShippingAddressResource($this->shippingAddress),
            // 'client_contact' => ClientContactResource::collection($this->clientContacts),
            // 'custom_info' => CustomInfoResource::collection($this->customInformation),
        ];
    }
}
