<div class="d-flex align-items-center  mb-5">
   @if ($supplier)
   <label class="fs-6 fw-semibold mb-2 me-3 required " style="width: 150px;">@lang('sales::fields.supplier')</label>

   @else
   <label class="fs-6 fw-semibold mb-2 me-3 required " style="width: 150px;">@lang('sales::fields.client')</label>

   @endif
    <select id="client_id" class="form-select select-2 form-select-solid"
        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" required name="client_id">
        @if ($supplier)
        <option value="">@lang('purchases::fields.select_supplier')</option>
        @else
        <option value="">@lang('sales::fields.select_client')</option>

        @endif
        @foreach ($clients as $client)
            <option value="{{ $client->id }}" data-name="{{ $client->name }}"
                data-mobile_number="{{ $client->mobile_number }}" data-email="{{ $client->email }}"
                data-tax_number="{{ $client->tax_number }}"
                data-billing_address="{{ $client->billingAddress?->city . ' - ' . $client->billingAddress?->street_name }}"
                data-billing_city="{{ $client->billingAddress?->city }}"
                data-billing_street_name="{{ $client->billingAddress?->street_name }}">
                {{ $client->name }}
            </option>
        @endforeach
    </select>
    <a class="link" id="addNewAccountBtn">
        <i class="ki-outline ki-plus-square toggle-off fs-1 me-0 mx-2"></i>
    </a>
</div>

<div class="  mb-5" id="dev-client_name">
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.client_name')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;" id="client_name">--</label>
</div>

<div class=" mb-5" id="dev-billing_address">
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.Billing Address')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;" id="billing_address">--</label>
</div>

<div class=" mb-5 " id="dev-mobile_number">
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.mobile_number')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;" id="mobile_number">--</label>
</div>
<div class="  mb-5" id="dev-email">
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.email')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;" id="email">--</label>
</div>
<div class="  mb-5" id="dev-tax_number">
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.tax_number')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;" id="tax_number">--</label>
</div>
