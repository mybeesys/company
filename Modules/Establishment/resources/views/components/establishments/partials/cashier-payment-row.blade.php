@php
    $accountId = $row['account_id'] ?? null;
@endphp
<div class="border border-dashed border-gray-300 rounded p-4 cashier-payment-row" data-cashier-row>
    @if (! empty($row['id']))
        <input type="hidden" name="cashier_payment_rows[{{ $index }}][id]" value="{{ $row['id'] }}">
    @endif
    <div class="row g-4 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-2">@lang('establishment::fields.name')</label>
            <input type="text" class="form-control form-control-solid"
                name="cashier_payment_rows[{{ $index }}][name_ar]"
                value="{{ $row['name_ar'] ?? '' }}"
                placeholder="@lang('establishment::fields.name')">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold mb-2">@lang('establishment::fields.name_en')</label>
            <input type="text" class="form-control form-control-solid"
                name="cashier_payment_rows[{{ $index }}][name_en]"
                value="{{ $row['name_en'] ?? '' }}"
                placeholder="@lang('establishment::fields.name_en')">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold mb-2">@lang('accounting::lang.account')</label>
            <select name="cashier_payment_rows[{{ $index }}][account_id]"
                class="form-select form-select-solid select-2-cashier w-100 cashier-account-select"
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
        <div class="col-md-2 d-flex gap-2 pb-1">
            <button type="button" class="btn btn-sm btn-light-danger cashier-remove-row"
                title="@lang('messages.delete')">
                <i class="ki-outline ki-trash fs-4"></i>
            </button>
        </div>
    </div>
</div>
