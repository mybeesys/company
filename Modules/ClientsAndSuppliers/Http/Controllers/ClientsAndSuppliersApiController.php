<?php

namespace Modules\ClientsAndSuppliers\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Accounting\Services\ContactAccountProvisioner;
use Modules\ClientsAndSuppliers\Models\Contact;
use Modules\ClientsAndSuppliers\Transformers\ContactResource;
use Modules\ClientsAndSuppliers\Transformers\CountriesResource;
use Modules\General\Models\Country;
use Modules\General\Models\Transaction;

class ClientsAndSuppliersApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function clients(Request $request)
    {
        $query = Contact::query()
            ->where('business_type', 'customer')
            ->with(['billingAddress.country_'])
            ->orderBy('name')
            ->orderBy('id');

        if ($request->boolean('active_only', true)) {
            $query->where('status', 'active');
        }

        $search = trim((string) ($request->input('q') ?? $request->input('search') ?? ''));
        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like) {
                $builder->where('name', 'like', $like)
                    ->orWhere('mobile_number', 'like', $like)
                    ->orWhere('phone_number', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('tax_number', 'like', $like);
            });
        }

        return ContactResource::collection($query->get());
    }

    public function suppliers()
    {
        $contacts = Contact::query()
            ->where('business_type', 'supplier')
            ->with(['billingAddress.country_'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return ContactResource::collection($contacts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $name = trim((string) ($request->input('client_name') ?: $request->input('name') ?: ''));
        $request->merge(['client_name' => $name]);
        $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
        ]);

        $businessType = strtolower(trim((string) ($request->input('business_type') ?: 'customer')));
        if (! in_array($businessType, ['customer', 'supplier'], true)) {
            $businessType = 'customer';
        }

        try {
            DB::beginTransaction();

            $existing = $this->findExistingCashierContact(
                $businessType,
                $request->input('mobile_number'),
                $request->input('phone_number')
            );
            if ($existing) {
                $existing->load(['billingAddress.country_']);
                DB::commit();

                return $this->contactSavedResponse($existing, false);
            }

            $accountId = $this->tryResolveAccountId($businessType, $name);

            $contact = Contact::create([
                'name' => $name,
                'business_type' => $businessType,
                'phone_number' => $this->nullableTrim($request->input('phone_number')),
                'mobile_number' => $this->nullableTrim($request->input('mobile_number')),
                'email' => $this->nullableTrim($request->input('email')),
                'tax_number' => $this->nullableTrim($request->input('tax_number')),
                'point_of_sale_client' => 1,
                'status' => 'active',
                'account_id' => $accountId,
            ]);

            if (
                $request->billing_street_name || $request->billing_city || $request->billing_state
                || $request->billing_postal_code || $request->building_number || $request->billing_country
            ) {
                $contact->billingAddress()->create([
                    'street_name' => $request->billing_street_name,
                    'city' => $request->billing_city,
                    'state' => $request->billing_state,
                    'postal_code' => $request->billing_postal_code,
                    'building_number' => $request->building_number,
                    'country' => $request->billing_country,
                ]);
            }

            $contact->load(['billingAddress.country_']);
            DB::commit();

            return $this->contactSavedResponse($contact, true);
        } catch (ValidationException $e) {
            DB::rollBack();

            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $contact = Contact::query()->with(['billingAddress.country_'])->find($id);
        if (! $contact) {
            return response()->json(['message' => 'reach non existent customer / supplier'], 404);
        }

        return $this->contactSavedResponse($contact, false, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $contact = Contact::find($request->id);
        if (! $contact) {
            return response()->json(['message' => 'reach non existent customer / supplier'], 404);
        }
        try {
            DB::beginTransaction();
            $name = trim((string) ($request->input('client_name') ?: $request->input('name') ?: $contact->name));
            $contact->update([
                'name' => $name !== '' ? $name : $contact->name,
                'phone_number' => $request->exists('phone_number')
                    ? $this->nullableTrim($request->input('phone_number'))
                    : $contact->phone_number,
                'mobile_number' => $request->exists('mobile_number')
                    ? $this->nullableTrim($request->input('mobile_number'))
                    : $contact->mobile_number,
                'email' => $request->exists('email')
                    ? $this->nullableTrim($request->input('email'))
                    : $contact->email,
                'tax_number' => $request->exists('tax_number')
                    ? $this->nullableTrim($request->input('tax_number'))
                    : $contact->tax_number,
            ]);

            if (
                $request->billing_street_name || $request->billing_city || $request->billing_state
                || $request->billing_postal_code || $request->building_number || $request->billing_country
            ) {
                $contact->billingAddress()->delete();
                $contact->billingAddress()->create([
                    'street_name' => $request->billing_street_name,
                    'city' => $request->billing_city,
                    'state' => $request->billing_state,
                    'postal_code' => $request->billing_postal_code,
                    'building_number' => $request->building_number,
                    'country' => $request->billing_country,
                ]);
            }

            $contact->load(['billingAddress.country_']);
            DB::commit();

            return $this->contactSavedResponse($contact, false);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'something went wrong'], 500);
        }
    }

    public function updateStatus($id)
    {
        $contact = Contact::query()->with(['billingAddress.country_'])->find($id);
        if (! $contact) {
            return response()->json(['message' => 'reach non existent customer / supplier'], 404);
        }

        $contact->status = $contact->status == 'active' ? 'inactive' : 'active';
        $contact->save();

        return $this->contactSavedResponse($contact, false);
    }

    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        if (! $contact) {
            return response()->json(['message' => 'reach non existent customer / supplier'], 404);
        }
        $count = Transaction::where('contact_id', $id)
            ->count();

        $contact = Contact::findOrFail($id);
        if (! $contact) {
            return response()->json(['message' => 'reach non existent customer / supplier'], 404);
        }

        if ($count == 0) {
            if (! $contact->is_default) {
                $contact->delete();
            }

            return response()->json(['message' => 'deleted successfully'], 200);
        } else {

            if ($contact->business_type == 'customer') {
                return response()->json(['message' => 'you cannot delete this client'], 200);
            }

            return response()->json(['message' => 'you cannot delete this supplier'], 200);
        }
    }

    public function countries()
    {

        $countries = Country::all();

        return CountriesResource::collection($countries);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    private function contactSavedResponse(Contact $contact, bool $created, int $status = 0)
    {
        $payload = (new ContactResource($contact))->resolve();

        return response()->json(array_merge($payload, [
            'data' => $payload,
            'created' => $created,
        ]), $status > 0 ? $status : ($created ? 201 : 200));
    }

    private function tryResolveAccountId(string $businessType, string $name): ?int
    {
        try {
            return ContactAccountProvisioner::resolveAccountIdForRequest($businessType, $name, null);
        } catch (\Throwable) {
            return null;
        }
    }

    private function findExistingCashierContact(
        string $businessType,
        mixed $mobile,
        mixed $phone
    ): ?Contact {
        $mobile = $this->nullableTrim($mobile);
        $phone = $this->nullableTrim($phone);
        if ($mobile === null && $phone === null) {
            return null;
        }

        $query = Contact::query()->where('business_type', $businessType);
        $query->where(function ($builder) use ($mobile, $phone) {
            if ($mobile !== null) {
                $builder->orWhere('mobile_number', $mobile)->orWhere('phone_number', $mobile);
            }
            if ($phone !== null && $phone !== $mobile) {
                $builder->orWhere('mobile_number', $phone)->orWhere('phone_number', $phone);
            }
        });

        $match = $query->orderBy('id')->first();
        if ($match && $mobile === null && $phone === null) {
            return null;
        }

        return $match;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
