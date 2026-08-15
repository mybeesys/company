@php
    $valueType = $row['value_type'] ?? 'cost';
    $value = $row['value'] ?? null;
    $accountId = $row['account_id'] ?? null;
    $isActive = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOL);
@endphp
<div class="border border-dashed border-gray-300 rounded p-3 p-lg-4 internal-consumption-row" data-internal-consumption-row>
    @if (! empty($row['id']))
        <input type="hidden" name="internal_consumption_rows[{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif
    <div class="row g-3 g-lg-4 align-items-end">
        <div class="col-lg-2 col-md-6">
            <label class="form-label fw-semibold mb-2 d-lg-none">@lang('establishment::fields.name')</label>
            <input type="text" class="form-control form-control-solid"
                name="internal_consumption_rows[{{ $index }}][name_ar]"
                value="{{ $row['name_ar'] ?? '' }}"
                placeholder="@lang('establishment::fields.name')">
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label fw-semibold mb-2 d-lg-none">@lang('establishment::fields.name_en')</label>
            <input type="text" class="form-control form-control-solid"
                name="internal_consumption_rows[{{ $index }}][name_en]"
                value="{{ $row['name_en'] ?? '' }}"
                placeholder="@lang('establishment::fields.name_en')">
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label fw-semibold mb-2 d-lg-none">@lang('establishment::fields.internal_consumption_value_type')</label>
            <select name="internal_consumption_rows[{{ $index }}][value_type]"
                class="form-select form-select-solid internal-consumption-value-type">
                <option value="cost" @selected($valueType === 'cost')>@lang('establishment::fields.internal_consumption_value_type_cost')</option>
                <option value="percent" @selected($valueType === 'percent')>@lang('establishment::fields.internal_consumption_value_type_percent')</option>
                <option value="fixed" @selected($valueType === 'fixed')>@lang('establishment::fields.internal_consumption_value_type_fixed')</option>
            </select>
        </div>
        <div class="col-lg-1 col-md-4 internal-consumption-value-wrap">
            <label class="form-label fw-semibold mb-2 d-lg-none">@lang('establishment::fields.internal_consumption_value')</label>
            <input type="number" step="0.01" min="0"
                class="form-control form-control-solid internal-consumption-value-input"
                name="internal_consumption_rows[{{ $index }}][value]"
                value="{{ $valueType === 'cost' ? '' : $value }}"
                placeholder="{{ $valueType === 'cost' ? '—' : '0' }}"
                @if ($valueType === 'cost') disabled @endif>
        </div>
        <div class="col-lg-3 col-md-8">
            <label class="form-label fw-semibold mb-2 d-lg-none">@lang('establishment::fields.internal_consumption_collection_account')</label>
            <select name="internal_consumption_rows[{{ $index }}][account_id]"
                class="form-select form-select-solid select-2-internal-consumption w-100 internal-consumption-account-select"
                data-placeholder="@lang('messages.select')">
                <option value=""></option>
                @foreach ($accounts ?? [] as $account)
                    <option value="{{ $account->id }}" @selected((string) $accountId === (string) $account->id)>
                        {{ $locale === 'ar'
                            ? trim(($account->gl_code ? $account->gl_code.' — ' : '').($account->name_ar ?? ''))
                            : trim(($account->gl_code ? $account->gl_code.' — ' : '').($account->name_en ?? $account->name_ar ?? '')) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-1 col-md-4">
            <label class="form-label fw-semibold mb-2 d-lg-none">@lang('establishment::fields.active')</label>
            <div class="form-check form-switch form-check-custom form-check-solid justify-content-lg-center py-lg-2">
                <input type="hidden" name="internal_consumption_rows[{{ $index }}][is_active]" value="0">
                <input class="form-check-input" type="checkbox"
                    name="internal_consumption_rows[{{ $index }}][is_active]"
                    value="1" @checked($isActive)>
            </div>
        </div>
        <div class="col-lg-1 col-md-2 d-flex justify-content-lg-center pb-1">
            <button type="button" class="btn btn-sm btn-light-danger internal-consumption-remove-row"
                title="@lang('messages.delete')">
                <i class="ki-outline ki-trash fs-4"></i>
            </button>
        </div>
    </div>
</div>
