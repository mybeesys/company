<?php

namespace Modules\ClientsAndSuppliers\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Exceptions\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Models\AccountingAccount;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\General\Models\Country;
use Modules\General\Models\Transaction;
use Modules\General\Models\TransactionPayments;
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

        $businessType = $business_type == 'clients' ? 'customer' : 'supplier';
        $this->abortUnlessContactTypeEntitled($businessType);
        $create_url = $business_type == 'clients' ? 'client-create' : 'supplier-create';
        if ($request->ajax()) {
            $contacts = Contact::where('business_type', $businessType)->select('id', 'name', 'mobile_number', 'email', 'commercial_register', 'tax_number', 'status');

            return Contact::getContactsTable($contacts);
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
        $create_page = Route::currentRouteName();
        $this->abortUnlessContactTypeEntitled($create_page === 'supplier-create' ? 'supplier' : 'customer');

        $countries = Country::all(); // DB::connection('mysql')->table('countries')->get();
        $payment_terms = SalesUtile::paymentTerms();
        $accounts = AccountingAccount::forDropdown();

        $parents_account = AccountingAccount::all();
        $account_main_types = AccountingUtil::account_type();
        $account_category = AccountingUtil::account_category();

        if ($create_page == 'supplier-create') {
            return view('clientsandsuppliers::Client.create.supplier', compact('countries', 'parents_account', 'account_category', 'account_main_types', 'accounts', 'payment_terms'));
        }

        return view('clientsandsuppliers::Client.create.create', compact('countries', 'parents_account', 'account_category', 'account_main_types', 'accounts', 'payment_terms'));
    }

    public function edit($id)
    {
        // $countries = DB::connection('mysql')->table('countries')->get();
        $contact = Contact::find($id);
        if (! $contact) {
            return redirect()->route('clients')->with('error', __('clientsandsuppliers::general.reach-non-existent-customer'));
        }
        $this->abortUnlessContactTypeEntitled($contact->business_type);

        $countries = Country::all();
        $payment_terms = SalesUtile::paymentTerms();
        $accounts = AccountingAccount::forDropdown();

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

        // return $request;
        $this->abortUnlessContactTypeEntitled((string) $request->input('business_type', 'customer'));
        $this->validateRequiredAccountingAccount($request);

        try {
            DB::beginTransaction();

            if ($request->ajax()) {
                $attachment_name = null;
                if ($request->hasFile('attachment')) {
                    $attachment = $request->file('attachment');
                    $attachment_name = $attachment->store('customers', 'public');
                }

                $contact = Contact::create([
                    'name' => $request->client_name,
                    'business_type' => $request->business_type,
                    'phone_number' => $request->phone_number,
                    'mobile_number' => $request->mobile_number,
                    'website' => $request->website,
                    'email' => $request->email,
                    'point_of_sale_client' => $request->has('point_of_sale_client') ? 1 : 0,
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
                    $billingAddress = $contact->billingAddress()->create([
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
                $attachment_name = $attachment->store('customers', 'public');
            }

            $contact = Contact::create([
                'name' => $request->client_name,
                'business_type' => $request->business_type,
                'phone_number' => $request->phone_number,
                'mobile_number' => $request->mobile_number,
                'website' => $request->website,
                'email' => $request->email,
                'point_of_sale_client' => $request->has('point_of_sale_client') ? 1 : 0,
                'tax_number' => $request->tax_number,
                'commercial_register' => $request->commercial_register,
                'file_path' => $attachment_name,
                'status' => 'active',
                'payment_terms' => $request->payment_terms,
                'account_id' => $request->account_id,
                'credit_limit' => $request->credit_limit,

            ]);

            if ($request->contact_customLable) {
                foreach ($request->contact_customLable as $index => $label) {
                    if (! empty($label) && ! empty($request->contact_customValue[$index])) {
                        $contact->customInformation()->create([
                            'lable' => $label,
                            'value' => $request->contact_customValue[$index],
                            'table_name' => 'contacts',
                        ]);
                    }
                }
            }

            if (
                $request->billing_street_name || $request->billing_city || $request->billing_state
                || $request->billing_postal_code || $request->building_number || $request->billing_country
            ) {
                $billingAddress = $contact->billingAddress()->create([
                    'street_name' => $request->billing_street_name,
                    'city' => $request->billing_city,
                    'state' => $request->billing_state,
                    'postal_code' => $request->billing_postal_code,
                    'building_number' => $request->building_number,
                    'country' => $request->billing_country,
                ]);

                if ($request->billing_customLable) {
                    foreach ($request->billing_customLable as $index => $label) {
                        if (! empty($label) && ! empty($request->billing_customValue[$index])) {
                            $billingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->billing_customValue[$index],
                                'table_name' => 'billing_addresses',
                            ]);
                        }
                    }
                }
            }

            if (
                $request->shipping_street_name || $request->shipping_city || $request->shipping_state
                || $request->shipping_postal_code || $request->shipping_country
            ) {
                $shippingAddress = $contact->shippingAddress()->create([
                    'street_name' => $request->shipping_street_name,
                    'city' => $request->shipping_city,
                    'state' => $request->shipping_state,
                    'postal_code' => $request->shipping_postal_code,
                    'country' => $request->shipping_country,
                ]);

                if ($request->shipping_customLable) {
                    foreach ($request->shipping_customLable as $index => $label) {
                        if (! empty($label) && ! empty($request->shipping_customValue[$index])) {
                            $shippingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->shipping_customValue[$index],
                                'table_name' => 'shipping_addresses',
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
                        if (! empty($label) && ! empty($request->bankInfo_customValue[$index])) {
                            $bankAccountInformation->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->bankInfo_customValue[$index],
                                'table_name' => 'bank_account_information',
                            ]);
                        }
                    }
                }
            }

            if (! empty($request->client_contact_name) && ! in_array(null, $request->client_contact_name, true)) {
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
            if ($request->business_type == 'customer') {
                return redirect()->route('clients')->with('success', __('messages.add_successfully'));
            }

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
        if (! $contact) {
            return redirect()->route('clients')->with('error', __('clientsandsuppliers::general.reach-non-existent-customer'));
        }
        $this->abortUnlessContactTypeEntitled($contact->business_type);

        $isSupplier = $contact->business_type !== 'customer';
        $invoiceTypes = $isSupplier ? ['purchases', 'purchases-return'] : ['sell', 'sell-return'];

        $invoicesQuery = Transaction::query()
            ->where('contact_id', $contact->id)
            ->whereIn('type', $invoiceTypes)
            ->where('status', 'approved');

        $totals = (clone $invoicesQuery)
            ->selectRaw('COUNT(*) as invoices_count')
            ->selectRaw("SUM(CASE WHEN payment_status IN ('due','partial') THEN 1 ELSE 0 END) as open_invoices_count")
            ->selectRaw('COALESCE(SUM(final_total),0) as invoices_total')
            ->first();

        $paidTotal = TransactionPayments::query()
            ->whereIn('transaction_id', (clone $invoicesQuery)->select('id'))
            ->sum('amount');

        $paidTotal = (float) $paidTotal;
        $invoicesTotal = (float) ($totals->invoices_total ?? 0);
        $outstandingTotal = max(0, $invoicesTotal - $paidTotal);

        $ageingBase = 'COALESCE(due_date, transaction_date)';
        $ageing = (clone $invoicesQuery)
            ->whereIn('payment_status', ['due', 'partial'])
            ->selectRaw("COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), $ageingBase) BETWEEN 0 AND 30 THEN final_total ELSE 0 END),0) as b0_30")
            ->selectRaw("COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), $ageingBase) BETWEEN 31 AND 60 THEN final_total ELSE 0 END),0) as b31_60")
            ->selectRaw("COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), $ageingBase) BETWEEN 61 AND 90 THEN final_total ELSE 0 END),0) as b61_90")
            ->selectRaw("COALESCE(SUM(CASE WHEN DATEDIFF(CURDATE(), $ageingBase) > 90 THEN final_total ELSE 0 END),0) as b90_plus")
            ->first();

        $recentInvoices = (clone $invoicesQuery)
            ->orderByDesc('transaction_date')
            ->limit(5)
            ->get(['id', 'ref_no', 'type', 'transaction_date', 'due_date', 'payment_status', 'final_total']);

        $recentPayments = TransactionPayments::query()
            ->with(['transaction', 'account'])
            ->where('payment_for', $contact->id)
            ->orderByDesc('paid_on')
            ->limit(5)
            ->get();

        $viewAllInvoicesUrl = $isSupplier ? route('purchase-invoices') : route('invoices');
        $viewAllPaymentsUrl = $isSupplier ? route('suppliers-receipts') : route('receipts');
        $country_bank = null;
        $country_billingAddress = null;
        $country_shippingAddress = null;

        if (! empty($contact->bankAccountInformation) && ! empty($contact->bankAccountInformation->country_bank)) {
            $country_bank = Country::find($contact->bankAccountInformation->country_bank);
        }

        if (! empty($contact->billingAddress) && ! empty($contact->billingAddress->country)) {
            $country_billingAddress = Country::find($contact->billingAddress->country);
        }

        if (! empty($contact->shippingAddress) && ! empty($contact->shippingAddress->country)) {
            $country_shippingAddress = Country::find($contact->shippingAddress->country);
        }

        $previous = Contact::where('id', '<', $id)->where('business_type', $contact->business_type)->orderBy('id', 'desc')->first();

        $next = Contact::where('id', '>', $id)->where('business_type', $contact->business_type)->orderBy('id', 'asc')->first();
        $clients = Contact::where('business_type', $contact->business_type)->get();

        return view('clientsandsuppliers::Client.show.show', compact(
            'contact',
            'clients',
            'previous',
            'next',
            'country_bank',
            'country_billingAddress',
            'country_shippingAddress',
            'isSupplier',
            'totals',
            'paidTotal',
            'outstandingTotal',
            'ageing',
            'recentInvoices',
            'recentPayments',
            'viewAllInvoicesUrl',
            'viewAllPaymentsUrl'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // return $request;
        // dd($request->hasFile('attachment'),$request->file('attachment'));
        $this->validateRequiredAccountingAccount($request);

        try {
            $contact = Contact::find($request->id);
            if (! $contact) {
                return redirect()->back()->with('error', __('messages.something_went_wrong'));
            }
            $this->abortUnlessContactTypeEntitled($contact->business_type);

            $attachment_name = null;

            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('customers', 'public');
            }

            DB::beginTransaction();
            $contact->update([
                'name' => $request->client_name,
                'phone_number' => $request->phone_number,
                'mobile_number' => $request->mobile_number,
                'website' => $request->website,
                'email' => $request->email,
                'point_of_sale_client' => $request->has('point_of_sale_client') ? 1 : 0,
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
                    if (! empty($label) && ! empty($request->contact_customValue[$index])) {
                        $contact->customInformation()->create([
                            'lable' => $label,
                            'value' => $request->contact_customValue[$index],
                            'table_name' => 'contacts',
                        ]);
                    }
                }
            }

            if (
                $request->billing_street_name || $request->billing_city || $request->billing_state
                || $request->billing_postal_code || $request->building_number || $request->billing_country
            ) {
                $contact->billingAddress()->delete();
                $billingAddress = $contact->billingAddress()->create([
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
                        if (! empty($label) && ! empty($request->billing_customValue[$index])) {
                            $billingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->billing_customValue[$index],
                                'table_name' => 'billing_addresses',
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
                $shippingAddress = $contact->shippingAddress()->create([
                    'street_name' => $request->shipping_street_name,
                    'city' => $request->shipping_city,
                    'state' => $request->shipping_state,
                    'postal_code' => $request->shipping_postal_code,
                    'country' => $request->shipping_country,
                ]);

                if ($request->shipping_customLable) {
                    $shippingAddress->customInformation()->delete();
                    foreach ($request->shipping_customLable as $index => $label) {
                        if (! empty($label) && ! empty($request->shipping_customValue[$index])) {
                            $shippingAddress->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->shipping_customValue[$index],
                                'table_name' => 'shipping_addresses',
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
                        if (! empty($label) && ! empty($request->bankInfo_customValue[$index])) {
                            $bankAccountInformation->customInformation()->create([
                                'lable' => $label,
                                'value' => $request->bankInfo_customValue[$index],
                                'table_name' => 'bank_account_information',
                            ]);
                        }
                    }
                }
            }

            if (! empty($request->client_contact_name) && ! in_array(null, $request->client_contact_name, true)) {
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
            if ($contact->business_type == 'customer') {
                return redirect()->route('clients')->with('success', __('messages.updated_successfully'));
            }

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

    /**
     * Customers need sales (or purchases for shared hub create from PO flows).
     * Suppliers need purchases only.
     */
    protected function abortUnlessContactTypeEntitled(?string $businessType): void
    {
        $businessType = $businessType === 'supplier' ? 'supplier' : 'customer';
        $required = $businessType === 'supplier'
            ? 'purchases'
            : ['sales', 'purchases'];

        if (! tenant_entitled($required)) {
            abort(403, __('responses.entitlement_forbidden'));
        }
    }

    protected function validateRequiredAccountingAccount(Request $request): void
    {
        $accountId = (int) $request->input('account_id');
        if ($accountId <= 0 || ! AccountingAccount::query()->whereKey($accountId)->exists()) {
            throw ValidationException::withMessages([
                'account_id' => __('clientsandsuppliers::fields.accounting_account_required'),
            ]);
        }
    }
}
