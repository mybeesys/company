<div class=" align-items-center  mb-5" id="div-storehouse" style="display: none">
    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 100px;">@lang('sales::fields.storehouse')</label>
    <select id="storehouse" class="form-select select-2 form-select-solid" 
        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="storehouse">
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

</div>


<div class=" align-items-center  mb-5" id="div-cash_account" >
    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 100px;">@lang('accounting::lang.account')</label>

    <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account " style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="cash_account"
        id="cash_account">

        <option value="">@lang('sales::lang.payment_account_select')</option>
        @foreach ($accounts as $account)
            <option value="{{ $account->id }}">
                @if (app()->getLocale() == 'ar')
                    {{ $account->name_ar }} - <span class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                @else
                    {{ $account->name_en }} - <span class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                @endif
            </option>
        @endforeach
    </select>
</div>

<div class="d-flex align-items-center  mb-5">
    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 100px;">@lang('sales::fields.client')</label>
    <select id="client_id" class="form-select select-2 form-select-solid" required
        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="client_id">
        <option value="">@lang('sales::fields.select_client')</option>
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
