@php
    $feeType = (string) ($row['service_fee_type'] ?? '0');
    $applicationType = (string) ($row['application_type'] ?? '1');
    $calculationMethod = (string) ($row['calculation_method'] ?? '0');
    $autoApplyType = (string) ($row['auto_apply_type'] ?? '');
    $diningTypeIds = $row['dining_type_ids'] ?? [];
    $isTaxable = filter_var($row['taxable'] ?? false, FILTER_VALIDATE_BOOL);
    $isActive = filter_var($row['active'] ?? true, FILTER_VALIDATE_BOOL);
    $locale = $locale ?? app()->getLocale();
@endphp
<div class="border rounded p-4 service-fee-row bg-body" data-service-fee-row>
    @if (! empty($row['id']))
        <input type="hidden" name="service_fee_rows[{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fs-6 fw-bold mb-1">@lang('establishment::general.service_fee_item_title')</h4>
            <span class="text-muted fs-8">@lang('establishment::general.service_fee_item_subtitle')</span>
        </div>
        <div class="d-flex align-items-center gap-4">
            <div class="form-check form-switch form-check-custom form-check-solid">
                <input type="hidden" name="service_fee_rows[{{ $index }}][active]" value="0">
                <input class="form-check-input" type="checkbox"
                    name="service_fee_rows[{{ $index }}][active]"
                    value="1" @checked($isActive)>
                <label class="form-check-label fw-semibold">@lang('establishment::fields.active')</label>
            </div>
            <button type="button" class="btn btn-sm btn-light-danger service-fee-remove-row" title="@lang('messages.delete')">
                <i class="ki-outline ki-trash fs-4"></i>
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <label class="form-label fw-semibold">@lang('establishment::fields.name')</label>
            <input type="text" class="form-control form-control-solid"
                name="service_fee_rows[{{ $index }}][name_ar]"
                value="{{ $row['name_ar'] ?? '' }}"
                placeholder="@lang('establishment::fields.name')">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">@lang('establishment::fields.name_en')</label>
            <input type="text" class="form-control form-control-solid"
                name="service_fee_rows[{{ $index }}][name_en]"
                value="{{ $row['name_en'] ?? '' }}"
                placeholder="@lang('establishment::fields.name_en')">
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_type')</label>
            <select name="service_fee_rows[{{ $index }}][service_fee_type]"
                class="form-select form-select-solid select-2-service-fee service-fee-type w-100"
                data-placeholder="@lang('messages.select')">
                <option value="0" @selected($feeType === '0')>@lang('establishment::fields.service_fee_type_amount')</option>
                <option value="1" @selected($feeType === '1')>@lang('establishment::fields.service_fee_type_percentage')</option>
            </select>
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_amount')</label>
            <input type="number" step="0.01" min="0"
                class="form-control form-control-solid"
                name="service_fee_rows[{{ $index }}][amount]"
                value="{{ $row['amount'] ?? '' }}"
                placeholder="0">
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_application_type')</label>
            <select name="service_fee_rows[{{ $index }}][application_type]"
                class="form-select form-select-solid select-2-service-fee w-100"
                data-placeholder="@lang('messages.select')">
                <option value="0" @selected($applicationType === '0')>@lang('establishment::fields.service_fee_app_type_item')</option>
                <option value="1" @selected($applicationType === '1')>@lang('establishment::fields.service_fee_app_type_order')</option>
            </select>
        </div>
        <div class="col-md-6 col-lg-3">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_calculation_method')</label>
            <select name="service_fee_rows[{{ $index }}][calculation_method]"
                class="form-select form-select-solid select-2-service-fee w-100"
                data-placeholder="@lang('messages.select')">
                <option value="0" @selected($calculationMethod === '0')>@lang('establishment::fields.service_fee_calc_method_total')</option>
                <option value="1" @selected($calculationMethod === '1')>@lang('establishment::fields.service_fee_calc_method_taxable')</option>
            </select>
        </div>
        <div class="col-12">
            <div class="form-check form-check-custom form-check-solid">
                <input type="hidden" name="service_fee_rows[{{ $index }}][taxable]" value="0">
                <input class="form-check-input" type="checkbox"
                    name="service_fee_rows[{{ $index }}][taxable]"
                    value="1" @checked($isTaxable)>
                <label class="form-check-label fw-semibold">@lang('establishment::fields.service_fee_taxable')</label>
            </div>
        </div>
    </div>

    <div class="separator my-5"></div>

    <div class="mb-3">
        <h5 class="fs-7 fw-bold mb-1">@lang('establishment::general.service_fee_auto_apply')</h5>
        <p class="text-muted fs-8 mb-0">@lang('establishment::general.service_fee_auto_apply_hint')</p>
    </div>

    <div class="row g-4">
        <div class="col-md-6 col-lg-4">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_auto_apply_type')</label>
            <select name="service_fee_rows[{{ $index }}][auto_apply_type]"
                class="form-select form-select-solid select-2-service-fee service-fee-auto-apply-type w-100"
                data-placeholder="@lang('messages.select')" data-allow-clear="true">
                <option value="">@lang('messages.select')</option>
                <option value="0" @selected($autoApplyType === '0')>@lang('establishment::fields.service_fee_auto_apply_dining')</option>
                <option value="1" @selected($autoApplyType === '1')>@lang('establishment::fields.service_fee_auto_apply_guest_count')</option>
                <option value="2" @selected($autoApplyType === '2')>@lang('establishment::fields.service_fee_auto_apply_payment')</option>
                <option value="3" @selected($autoApplyType === '3')>@lang('establishment::fields.service_fee_auto_apply_time_slot')</option>
            </select>
        </div>

        <div class="col-md-6 col-lg-8 service-fee-auto-apply-field" data-auto-apply="0" @if ($autoApplyType !== '0') style="display:none" @endif>
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_dining_options')</label>
            <select name="service_fee_rows[{{ $index }}][dining_type_ids][]"
                class="form-select form-select-solid select-2-service-fee w-100" multiple
                data-placeholder="@lang('messages.select')" data-allow-clear="true">
                @foreach ($diningTypes ?? [] as $diningType)
                    <option value="{{ $diningType->id }}" @selected(in_array((string) $diningType->id, array_map('strval', $diningTypeIds), true))>
                        {{ $locale === 'ar' ? ($diningType->name_ar ?? $diningType->name_en) : ($diningType->name_en ?? $diningType->name_ar) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 col-lg-4 service-fee-auto-apply-field" data-auto-apply="1" @if ($autoApplyType !== '1') style="display:none" @endif>
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_guest_count')</label>
            <input type="number" min="1" class="form-control form-control-solid"
                name="service_fee_rows[{{ $index }}][guestCount]"
                value="{{ $row['guestCount'] ?? '' }}"
                placeholder="0">
        </div>

        <div class="col-md-6 col-lg-4 service-fee-auto-apply-field" data-auto-apply="2" @if ($autoApplyType !== '2') style="display:none" @endif>
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_credit_type')</label>
            <select name="service_fee_rows[{{ $index }}][credit_type]"
                class="form-select form-select-solid select-2-service-fee service-fee-branch-payment w-100"
                data-placeholder="@lang('messages.select')" data-allow-clear="true">
                <option value="">@lang('messages.select')</option>
                @foreach ($cashierPaymentRows ?? [] as $method)
                    @php
                        $methodId = $method['id'] ?? $method->id ?? null;
                        $methodNameAr = $method['name_ar'] ?? $method->name_ar ?? '';
                        $methodNameEn = $method['name_en'] ?? $method->name_en ?? '';
                    @endphp
                    @if ($methodId)
                        <option value="{{ $methodId }}" @selected((string) ($row['credit_type'] ?? '') === (string) $methodId)>
                            {{ $locale === 'ar' ? ($methodNameAr ?: $methodNameEn) : ($methodNameEn ?: $methodNameAr) }}
                        </option>
                    @endif
                @endforeach
            </select>
            <div class="form-text text-muted fs-8 service-fee-branch-payment-empty @if (collect($cashierPaymentRows ?? [])->contains(fn ($m) => ! empty($m['id'] ?? null))) d-none @endif">
                @lang('establishment::general.service_fee_no_branch_payments')
            </div>
        </div>

        <div class="col-md-6 col-lg-4 service-fee-auto-apply-field" data-auto-apply="3" @if ($autoApplyType !== '3') style="display:none" @endif>
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_from_date')</label>
            <input type="datetime-local" class="form-control form-control-solid"
                name="service_fee_rows[{{ $index }}][from_date]"
                value="{{ $row['from_date'] ?? '' }}">
        </div>
        <div class="col-md-6 col-lg-4 service-fee-auto-apply-field" data-auto-apply="3" @if ($autoApplyType !== '3') style="display:none" @endif>
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_to_date')</label>
            <input type="datetime-local" class="form-control form-control-solid"
                name="service_fee_rows[{{ $index }}][to_date]"
                value="{{ $row['to_date'] ?? '' }}">
        </div>
    </div>
</div>
