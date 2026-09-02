@php($dup = $duplicateDefaults ?? null)
<div class="card" data-section="client-receipt" style="border: 0; box-shadow: none;">
    <div class="container">
        <div class="d-flex align-items-center mb-5">
            @if ($supplier)
                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px; flex-shrink: 0;">@lang('sales::fields.supplier')</label>
            @else
                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px; flex-shrink: 0;">@lang('sales::fields.client')</label>
            @endif
            <select id="client_id" class="form-select select-2 form-select-solid"
                style="padding: 0px 12px; border: 1px solid var(--bs-gray-300); width: 60% !important; max-width: 100%;" required name="client_id">
                @if ($supplier)
                    <option value="">@lang('purchases::fields.select_supplier')</option>
                @else
                    <option value="">@lang('sales::fields.select_client')</option>
                @endif
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected((int) old('client_id', $dup['client_id'] ?? 0) === (int) $client->id) data-name="{{ $client->name }}"
                        data-mobile_number="{{ $client->mobile_number }}" data-email="{{ $client->email }}"
                        data-tax_number="{{ $client->tax_number }}"
                        data-billing_address="{{ $client->billingAddress?->city . ' - ' . $client->billingAddress?->street_name }}"
                        data-billing_city="{{ $client->billingAddress?->city }}"
                        data-payment_terms="{{ $client->payment_terms }}"
                        data-billing_street_name="{{ $client->billingAddress?->street_name }}">
                        {{ $client->name }}
                    </option>
                @endforeach
            </select>
            @if ($supplier)
            @dashboardcan(\Modules\Purchases\Support\PurchasesPermissions::SUPPLIERS_CREATE)
            <a class="link flex-shrink-0" id="addNewAccountBtn" href="#" role="button">
                <i class="ki-outline ki-plus-square toggle-off fs-1 me-0 mx-2"></i>
            </a>
            @enddashboardcan
            @else
            @dashboardcan(\Modules\Sales\Support\SalesPermissions::CUSTOMERS_CREATE)
            <a class="link flex-shrink-0" id="addNewAccountBtn" href="#" role="button">
                <i class="ki-outline ki-plus-square toggle-off fs-1 me-0 mx-2"></i>
            </a>
            @enddashboardcan
            @endif
        </div>

        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px; flex-shrink: 0;" for="cost_center">@lang('accounting::lang.cost_center')</label>
            <select class="form-select select-2 form-select-solid kt_ecommerce_select2_cost_center" name="cost_center_id"
                id="cost_center"
                style="padding: 0px 12px; border: 1px solid var(--bs-gray-300); width: 60% !important; max-width: 100%;">
                <option value=""></option>
                @foreach ($cost_centers as $cost_center)
                    <option value="{{ $cost_center->id }}" @selected((int) old('cost_center_id', $dup['cost_center_id'] ?? 0) === (int) $cost_center->id)>
                        @if (app()->getLocale() == 'ar')
                            {{ $cost_center->name_ar }} — <span class="fw-semibold text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                        @else
                            {{ $cost_center->name_en }} — <span class="fw-semibold text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3" style="width: 150px; flex-shrink: 0;" for="notice">@lang('purchases::lang.description')</label>
            <input class="form-control form-control-solid custom-height" name="additionalNotes" value="{{ old('additionalNotes', $dup['additional_notes'] ?? '') }}"
                placeholder="@lang('purchases::lang.description')" id="notice" type="text" autocomplete="off">
        </div>
    </div>
</div>
