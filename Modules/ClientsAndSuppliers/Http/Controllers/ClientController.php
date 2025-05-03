<?php

namespace Modules\ClientsAndSuppliers\Http\Controllers;

use App\Http\Controllers\Controller;

use Carbon\Exceptions\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Country;
use Modules\Sales\Utils\SalesUtile;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //    return $contacts =  Contact::select('id', 'name', 'mobile_number', 'email', 'commercial_register', 'tax_number', 'status')->get();

        $business_type = Route::currentRouteName();

        $businessType = $business_type  == 'clients'  ? 'customer' : 'supplier';
        $create_url = $business_type  == 'clients'  ? 'client-create' : 'supplier-create';
        if ($request->ajax()) {
            $contacts =  Contact::where('business_type', $businessType)->select('id', 'name', 'mobile_number', 'email', 'commercial_register', 'tax_number', 'status');

            return  Contact::getContactsTable($contacts);
        }
        $columns = Contact::getContactsColumns();

        return view('clientsandsuppliers::Client.index', compact('columns', 'create_url', 'business_type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // dd(env('DB_CONNECTION')) ;
        $countries = Country::all(); //DB::connection('mysql')->table('countries')->get();
        $payment_terms = SalesUtile::paymentTerms();
        $accounts =  AccountingAccount::forDropdown();


        $parents_account = AccountingAccount::all();
        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();
        $create_page = Route::currentRouteName();

        if ($create_page == 'supplier-create')
            return view('clientsandsuppliers::Client.create.supplier', compact('countries', 'parents_account', 'account_category', 'account_main_types', 'accounts', 'payment_terms'));


        return view('clientsandsuppliers::Client.create.create', compact('countries', 'parents_account', 'account_category', 'account_main_types', 'accounts', 'payment_terms'));
    }



    public function edit($id)
    {
        // $countries = DB::connection('mysql')->table('countries')->get();
        $countries = Country::all();
        $payment_terms = SalesUtile::paymentTerms();
        $accounts =  AccountingAccount::forDropdown();

        $contact =  Contact::find($id);

        $parents_account = AccountingAccount::all();
        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();



        return view('clientsandsuppliers::Client.edit.edit', compact('countries', 'contact', 'parents_account', 'account_category', 'account_main_types', 'accounts', 'payment_terms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {


        try {
            DB::beginTransaction();

            if ($request->ajax()) {
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
                    'payment_terms' => $request->payment_terms,
                    'account_id' => $request->account_id,
                    'file_path' => $attachment_name,
                    'credit_limit' => $request->credit_limit,
                    'status' => 'active',

                ]);

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
                }
                DB::commit();
                return response()->json($contact);
            }
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
                    if ($name)
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


            if ($request->business_type == 'customer')
                return redirect()->route('clients')->with('success', __('messages.add_successfully'));
            return redirect()->route('suppliers')->with('success', __('messages.add_successfully'));
        } catch (Exception $e) {


            DB::rollBack();
            return redirect()->route('clients')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {

        $contact = Contact::find($id);
        if (!$contact) {
            return redirect()->route('clients')->with('error', __('clientsandsuppliers::general.reach-non-existent-customer'));
        }
        $country_bank = null;
        $country_billingAddress = null;
        $country_shippingAddress = null;

        if (!empty($contact->bankAccountInformation) && !empty($contact->bankAccountInformation->country_bank)) {
            $country_bank = Country::find($contact->bankAccountInformation->country_bank);
        }

        if (!empty($contact->billingAddress) && !empty($contact->billingAddress->country)) {
            $country_billingAddress = Country::find($contact->billingAddress->country);
        }

        if (!empty($contact->shippingAddress) && !empty($contact->shippingAddress->country)) {
            $country_shippingAddress = Country::find($contact->shippingAddress->country);
        }

        $previous = Contact::where('id', '<', $id)->where('business_type', $contact->business_type)->orderBy('id', 'desc')->first();

        $next = Contact::where('id', '>', $id)->where('business_type', $contact->business_type)->orderBy('id', 'asc')->first();
        $clients = Contact::where('business_type', $contact->business_type)->get();

        return view('clientsandsuppliers::Client.show.show', compact('contact', 'clients', 'previous', 'next', 'country_bank', 'country_billingAddress', 'country_shippingAddress'));
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // return $request;
        try {
            $attachment_name = null;
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/customers');
            }

            $contact = Contact::find($request->id);
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
                'payment_terms' => $request->payment_terms,
                'account_id' => $request->account_id,
                'credit_limit' => $request->credit_limit,


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
                $contact->billingAddress()->delete();
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
                $contact->shippingAddress()->delete();
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
                $contact->bankAccountInformation()->delete();
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
            if ($contact->business_type == 'customer')
                return redirect()->route('clients')->with('success', __('messages.updated_successfully'));
            return redirect()->route('suppliers')->with('success', __('messages.updated_successfully'));
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->route('clients')->with('error', __('messages.something_went_wrong'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
