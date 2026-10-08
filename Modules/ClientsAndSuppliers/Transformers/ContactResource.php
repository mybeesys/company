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
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'client_name' => (string) $this->name,
            'business_type' => (string) ($this->business_type ?? 'customer'),
            'mobile_number' => $this->mobile_number,
            'phone_number' => $this->phone_number,
            // 'website' => $this->website,
            'point_of_sale_client' => (int) ($this->point_of_sale_client ?? 0) === 1 ? 1 : 0,
            // 'payment_terms' => $this->payment_terms,
            'email' => $this->email,
            // 'commercial_register' => $this->commercial_register,
            'tax_number' => $this->tax_number,
            'status' => $this->status,
            // 'bank_account_info' => new BankAccountInformationResource($this->bankAccountInformation),
            // 'billing_address' => new BillingAddressResource($this->billingAddress),

            'street_name' => $this->billingAddress?->street_name ?? '',
            'city' => $this->billingAddress?->city ?? '',
            'state' => $this->billingAddress?->state ?? '',
            'postal_code' => $this->billingAddress?->postal_code ?? '',
            'building_number' => $this->billingAddress?->building_number ?? '',
            'country' => $this->billingAddress?->countryLabel() ?? '',

            // 'shipping_address' => new ShippingAddressResource($this->shippingAddress),
            // 'client_contact' => ClientContactResource::collection($this->clientContacts),
            // 'custom_info' => CustomInfoResource::collection($this->customInformation),
        ];
    }
}
