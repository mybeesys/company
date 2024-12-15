<?php

namespace Modules\ClientsAndSuppliers\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\Transformers\ContactResource;
use Modules\General\Models\Transaction;

class ClientsAndSuppliersApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function clients()
    {
        $contacts =  Contact::where('business_type', 'customer')->get();
        return ContactResource::collection($contacts);
    }


    public function suppliers()
    {
        $contacts =  Contact::where('business_type', 'supplier')->get();
        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_name' => ['required']
        ]);
        try {
            DB::beginTransaction();

            $attachment_name = null;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/customers');
            }

            $contact = Contact::create([
                'name' => $request->client_name,
                'business_type' => $request->business_type,
                'phone_number' => $request->phone_number,
                'mobile_number' => $request->mobile_number,
                'website' => $request->website,
                'email' => $request->email,
                'point_of_sale_client' => $request->has('point_of_sale_client')  ? 1 : 0,
                'tax_number' => $request->tax_number,
                'commercial_register' => $request->commercial_register,
                'file_path' => $attachment_name,
                'status' => 'active',
                'payment_terms' => $request->payment_terms,
                'account_id' => $request->account_id,

            ]);


            if ($request->contact_customLable) {
                foreach ($request->contact_customLable as $index => $label) {
                    if (!empty($label) && !empty($request->contact_customValue[$index])) {
                        $contact->customInformation()->create([
                            'lable' => $label,
                            'value' => $request->contact_customValue[$index],
                            'table_name' => 'contacts'
                        ]);
                    }
                }
            }


            if (
                $request->billing_street_name || $request->billing_city || $request->billing_state
                || $request->billing_postal_code || $request->building_number || $request->billing_country
            ) {
                $billingAddress =  $contact->billingAddress()->create([
                    'street_name' => $request->billing_street_name,
                    'city' => $request->billing_city,
                    'state' => $request->billing_state,
                    'postal_code' => $request->billing_postal_code,
                    'building_number' => $request->building_number,
                    'country' => $request->billing_country,
                ]);

                if ($request->billing_customLable) {
                    foreach ($request->billing_customLable as $index => $label) {
                        if (!empty($label) && !empty($request->billing_customValue[$index])) {
                            $billingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->billing_customValue[$index],
                                'table_name' => 'billing_addresses'
                            ]);
                        }
                    }
                }
            }


            if (
                $request->shipping_street_name || $request->shipping_city || $request->shipping_state
                || $request->shipping_postal_code || $request->shipping_country
            ) {
                $shippingAddress =  $contact->shippingAddress()->create([
                    'street_name' => $request->shipping_street_name,
                    'city' => $request->shipping_city,
                    'state' => $request->shipping_state,
                    'postal_code' => $request->shipping_postal_code,
                    'country' => $request->shipping_country,
                ]);

                if ($request->shipping_customLable) {
                    foreach ($request->shipping_customLable as $index => $label) {
                        if (!empty($label) && !empty($request->shipping_customValue[$index])) {
                            $shippingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->shipping_customValue[$index],
                                'table_name' => 'shipping_addresses'
                            ]);
                        }
                    }
                }
            }

            if (
                $request->bankInfo_bank_name || $request->bankInfo_bank_account_name || $request->bankInfo_country_bank
                || $request->bankInfo_currency || $request->bankInfo_iban_number || $request->bankInfo_bank_account_number
                || $request->bankInfo_swift_code || $request->bankInfo_bank_address
            ) {
                $bankAccountInformation = $contact->bankAccountInformation()->create([
                    'bank_name' => $request->bankInfo_bank_name,
                    'bank_account_name' => $request->bankInfo_bank_account_name,
                    'country_bank' => $request->bankInfo_country_bank,
                    'currency' => $request->bankInfo_currency,
                    'iban_number' => $request->bankInfo_iban_number,
                    'bank_account_number' => $request->bankInfo_bank_account_number,
                    'swift_code' => $request->bankInfo_swift_code,
                    'bank_address' => $request->bankInfo_bank_address,
                ]);
                if ($request->bankInfo_customLable) {
                    foreach ($request->bankInfo_customLable as $index => $label) {
                        if (!empty($label) && !empty($request->bankInfo_customValue[$index])) {
                            $bankAccountInformation->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->bankInfo_customValue[$index],
                                'table_name' => 'bank_account_information'
                            ]);
                        }
                    }
                }
            }

            if ($request->client_contact_name) {
                foreach ($request->client_contact_name as $index => $name) {
                    $contact->clientContacts()->create([
                        'name' => $name,
                        'email' => $request->client_contact_email[$index],
                        'mobile_number' => $request->client_contact_mobile_number[$index],
                        'alternative_mobile_number' => $request->alternative_mobile_number[$index],
                        'department' => $request->client_contact_department[$index],
                        'position' => $request->client_contact_position[$index],
                    ]);
                }
            }


            DB::commit();
            return response()->json(new ContactResource($contact), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json('something went wrong', 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json('reach non existent customer / supplier', 404);
        }
        return response()->json(new ContactResource($contact), 200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $contact = Contact::find($request->id);
        if (!$contact) {
            return response()->json('reach non existent customer / supplier', 404);
        }
        try {
            $attachment_name = null;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/customers');
            }


            DB::beginTransaction();
            $contact->update([
                'name' => $request->client_name,
                'phone_number' => $request->phone_number,
                'mobile_number' => $request->mobile_number,
                'website' => $request->website,
                'email' => $request->email,
                'point_of_sale_client' => $request->has('point_of_sale_client')  ? 1 : 0,
                'tax_number' => $request->tax_number,
                'commercial_register' => $request->commercial_register,
                'file_path' => $attachment_name,
                'status' => 'active',
                'payment_terms' => $request->payment_terms,
                'account_id' => $request->account_id,

            ]);


            if ($request->contact_customLable) {
                $contact->customInformation()->delete();
                foreach ($request->contact_customLable as $index => $label) {
                    if (!empty($label) && !empty($request->contact_customValue[$index])) {
                        $contact->customInformation()->create([
                            'lable' => $label,
                            'value' => $request->contact_customValue[$index],
                            'table_name' => 'contacts'
                        ]);
                    }
                }
            }


            if (
                $request->billing_street_name || $request->billing_city || $request->billing_state
                || $request->billing_postal_code || $request->building_number || $request->billing_country
            ) {
                $billingAddress =  $contact->billingAddress()->create([
                    'street_name' => $request->billing_street_name,
                    'city' => $request->billing_city,
                    'state' => $request->billing_state,
                    'postal_code' => $request->billing_postal_code,
                    'building_number' => $request->building_number,
                    'country' => $request->billing_country,
                ]);

                if ($request->billing_customLable) {
                    $billingAddress->customInformation()->delete();
                    foreach ($request->billing_customLable as $index => $label) {
                        if (!empty($label) && !empty($request->billing_customValue[$index])) {
                            $billingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->billing_customValue[$index],
                                'table_name' => 'billing_addresses'
                            ]);
                        }
                    }
                }
            }


            if (
                $request->shipping_street_name || $request->shipping_city || $request->shipping_state
                || $request->shipping_postal_code || $request->shipping_country
            ) {
                $shippingAddress =  $contact->shippingAddress()->create([
                    'street_name' => $request->shipping_street_name,
                    'city' => $request->shipping_city,
                    'state' => $request->shipping_state,
                    'postal_code' => $request->shipping_postal_code,
                    'country' => $request->shipping_country,
                ]);

                if ($request->shipping_customLable) {
                    $shippingAddress->customInformation()->delete();
                    foreach ($request->shipping_customLable as $index => $label) {
                        if (!empty($label) && !empty($request->shipping_customValue[$index])) {
                            $shippingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->shipping_customValue[$index],
                                'table_name' => 'shipping_addresses'
                            ]);
                        }
                    }
                }
            }

            if (
                $request->bankInfo_bank_name || $request->bankInfo_bank_account_name || $request->bankInfo_country_bank
                || $request->bankInfo_currency || $request->bankInfo_iban_number || $request->bankInfo_bank_account_number
                || $request->bankInfo_swift_code || $request->bankInfo_bank_address
            ) {
                $bankAccountInformation = $contact->bankAccountInformation()->create([
                    'bank_name' => $request->bankInfo_bank_name,
                    'bank_account_name' => $request->bankInfo_bank_account_name,
                    'country_bank' => $request->bankInfo_country_bank,
                    'currency' => $request->bankInfo_currency,
                    'iban_number' => $request->bankInfo_iban_number,
                    'bank_account_number' => $request->bankInfo_bank_account_number,
                    'swift_code' => $request->bankInfo_swift_code,
                    'bank_address' => $request->bankInfo_bank_address,
                ]);
                $bankAccountInformation->customInformation()->delete();

                if ($request->bankInfo_customLable) {
                    foreach ($request->bankInfo_customLable as $index => $label) {
                        if (!empty($label) && !empty($request->bankInfo_customValue[$index])) {
                            $bankAccountInformation->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->bankInfo_customValue[$index],
                                'table_name' => 'bank_account_information'
                            ]);
                        }
                    }
                }
            }

            if ($request->client_contact_name) {
                $contact->clientContacts()->delete();
                foreach ($request->client_contact_name as $index => $name) {
                    $contact->clientContacts()->create([
                        'name' => $name,
                        'email' => $request->client_contact_email[$index],
                        'mobile_number' => $request->client_contact_mobile_number[$index],
                        'alternative_mobile_number' => $request->alternative_mobile_number[$index],
                        'department' => $request->client_contact_department[$index],
                        'position' => $request->client_contact_position[$index],
                    ]);
                }
            }


            DB::commit();
            return response()->json(new ContactResource($contact), 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json('something went wrong', 500);
        }
    }


    public function updateStatus($id)
    {
        $contact = Contact::find($id);
        if (!$contact) {
            return response()->json('reach non existent customer / supplier', 404);
        }

        $contact->status = $contact->status == 'active' ? 'inactive' : 'active';
        $contact->save();

        return response()->json(new ContactResource($contact), 200);
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        if (!$contact) {
            return response()->json('reach non existent customer / supplier', 404);
        }
        $count = Transaction::where('contact_id', $id)
            ->count();

        $contact = Contact::findOrFail($id);
        if (!$contact) {
            return response()->json('reach non existent customer / supplier', 404);
        }

        if ($count == 0) {
            if (!$contact->is_default) {
                $contact->delete();
            }
            return response()->json('deleted successfully', 200);
        } else {

            if ($contact->business_type == 'customer')
                return response()->json('you cannot delete this client', 200);
            return response()->json('you cannot delete this supplier', 200);
        }
    }
}