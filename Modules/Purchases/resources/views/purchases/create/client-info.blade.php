<div class=" align-items-center  mb-5" id="div-storehouse" style="display: none">
    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 100px;">@lang('sales::fields.storehouse')</label>
    <select id="storehouse" class="form-select select-2 form-select-solid" required
        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="storehouse">
        @foreach ($establishments as $establishment)
            <option value="{{ $establishment->id }}">
                {{ $establishment->name }}
            </option>
        @endforeach
    </select>

</div>


<div class=" align-items-center  mb-5" id="div-cash_account"
    @if ($po) style="display: none;" @endif>
    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 100px;">@lang('accounting::lang.account')</label>

    <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account "
        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="cash_account"
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
    <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 100px;">@lang('purchases::fields.supplier')</label>
    <select id="client_id" class="form-select select-2 form-select-solid" required
        style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="client_id">
        <option value="">@lang('purchases::fields.select_supplier')</option>
        @foreach ($clients as $client)
            <option value="{{ $client->id }}" data-name="{{ $client->name }}"
                @if ($transaction?->contact_id == $client->id) selected @endif data-mobile_number="{{ $client->mobile_number }}"
                data-email="{{ $client->email }}" data-tax_number="{{ $client->tax_number }}"
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
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;"
        id="client_name">{{ $transaction?->client->name ?? '--' }}</label>
</div>

<div class=" mb-5" id="dev-billing_address" @if (!$transaction?->client && !$transaction?->client->billingAddress) style="display: none;" @endif>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.Billing Address')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;"
        id="billing_address">{{ $transaction?->client ? $transaction?->client->billingAddress?->city . ' - ' . $transaction?->client->billingAddress?->street_name : '--' }}</label>
</div>

<div class=" mb-5 " id="dev-mobile_number" @if (!$transaction?->client->mobile_number) style="display: none;" @endif>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.mobile_number')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;"
        id="mobile_number">{{ $transaction?->client->mobile_number ?? '--' }}</label>
</div>
<div class="  mb-5" id="dev-email" @if (!$transaction?->client->email) style="display: none;" @endif>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.email')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;"
        id="email">{{ $transaction?->client->email ?? '--' }}</label>
</div>
<div class="  mb-5" id="dev-tax_number" @if (!$transaction?->client->tax_number) style="display: none;" @endif>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;">@lang('clientsandsuppliers::fields.tax_number')</label>
    <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px;"
        id="tax_number">{{ $transaction?->client->tax_number ?? '--' }}</label>
</div>
