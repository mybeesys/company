{{--
  صف رسم واحد داخل تاب رسوم طريقة الدفع
  المتغيرات: $methodIndex, $feeIndex, $fee (array), $locale
--}}
@php
    $feeType        = (string) ($fee['fee_type']        ?? '0');
    $applicationType = (string) ($fee['application_type'] ?? '1');
    $isActive       = filter_var($fee['is_active'] ?? true, FILTER_VALIDATE_BOOL);
    $prefix         = "cashier_payment_rows[{$methodIndex}][fees][{$feeIndex}]";
@endphp
<div class="border border-gray-200 rounded p-3 pmf-fee-row bg-light-subtle">
    @if (!empty($fee['id']))
        <input type="hidden" name="{{ $prefix }}[id]" value="{{ $fee['id'] }}">
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge badge-light-info fw-bold fs-8">
            @if ($feeType === '1')
                @lang('establishment::fields.payment_method_fee_type_percent')
            @else
                @lang('establishment::fields.payment_method_fee_type_amount')
            @endif
            —
            @if ($applicationType === '0')
                @lang('establishment::fields.service_fee_app_type_item')
            @else
                @lang('establishment::fields.service_fee_app_type_order')
            @endif
        </span>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch form-check-custom form-check-solid">
                <input type="hidden" name="{{ $prefix }}[is_active]" value="0">
                <input class="form-check-input" type="checkbox"
                    name="{{ $prefix }}[is_active]" value="1" @checked($isActive)>
                <label class="form-check-label fw-semibold fs-8">@lang('establishment::fields.active')</label>
            </div>
            <button type="button" class="btn btn-icon btn-sm btn-light-danger pmf-remove-fee-btn">
                <i class="ki-outline ki-trash fs-5"></i>
            </button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold fs-8 mb-1">@lang('establishment::fields.name')</label>
            <input type="text" class="form-control form-control-solid form-control-sm"
                name="{{ $prefix }}[name_ar]"
                value="{{ $fee['name_ar'] ?? '' }}"
                placeholder="@lang('establishment::fields.name')">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold fs-8 mb-1">@lang('establishment::fields.name_en')</label>
            <input type="text" class="form-control form-control-solid form-control-sm"
                name="{{ $prefix }}[name_en]"
                value="{{ $fee['name_en'] ?? '' }}"
                placeholder="@lang('establishment::fields.name_en')">
        </div>
        <div class="col-md-4 col-lg-2">
            <label class="form-label fw-semibold fs-8 mb-1">@lang('establishment::fields.payment_method_fee_type')</label>
            <select name="{{ $prefix }}[fee_type]"
                class="form-select form-select-solid form-select-sm pmf-fee-type-select w-100">
                <option value="0" @selected($feeType === '0')>@lang('establishment::fields.payment_method_fee_type_amount')</option>
                <option value="1" @selected($feeType === '1')>@lang('establishment::fields.payment_method_fee_type_percent')</option>
            </select>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label fw-semibold fs-8 mb-1">
                @lang('establishment::fields.service_fee_amount')
                <span class="pmf-unit-label text-muted">
                    @if ($feeType === '1') % @else @get_format_currency() @endif
                </span>
            </label>
            <input type="number" step="0.01" min="0"
                class="form-control form-control-solid form-control-sm"
                name="{{ $prefix }}[amount]"
                value="{{ $fee['amount'] ?? '' }}"
                placeholder="0">
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label fw-semibold fs-8 mb-1">@lang('establishment::fields.service_fee_application_type')</label>
            <select name="{{ $prefix }}[application_type]"
                class="form-select form-select-solid form-select-sm w-100">
                <option value="0" @selected($applicationType === '0')>@lang('establishment::fields.service_fee_app_type_item')</option>
                <option value="1" @selected($applicationType === '1')>@lang('establishment::fields.service_fee_app_type_order')</option>
            </select>
        </div>
    </div>
</div>
