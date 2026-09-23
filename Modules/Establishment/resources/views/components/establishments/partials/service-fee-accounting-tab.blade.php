@php
    $accounts = $accounts ?? collect();
    $locale = $locale ?? app()->getLocale();
    $direction = strtoupper((string) ($row['fee_direction'] ?? 'COLLECTED'));
    if (! in_array($direction, ['COLLECTED', 'PAID'], true)) {
        $direction = 'COLLECTED';
    }
    $feeAccountId = (int) ($row['fee_account_id']
        ?? ($direction === 'PAID'
            ? ($row['expense_account_id'] ?? 0)
            : ($row['revenue_account_id'] ?? $row['credit_accounting_account_id'] ?? 0)));

    $accountLabel = function ($account) use ($locale) {
        return '('.$account->gl_code.') '
            .($locale === 'ar' ? ($account->name_ar ?: $account->name_en) : ($account->name_en ?: $account->name_ar));
    };
@endphp
<div class="service-fee-accounting-pane" data-service-fee-accounting>
    <div class="mb-4">
        <div class="fw-bold fs-6 text-gray-800">@lang('establishment::general.service_fee_accounting_title')</div>
        <div class="text-muted fs-7 mt-1">@lang('establishment::general.service_fee_accounting_hint')</div>
    </div>

    <div class="row g-4 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_direction')</label>
            <div class="d-flex gap-4 pt-2">
                <label class="form-check form-check-custom form-check-solid">
                    <input class="form-check-input service-fee-direction"
                        type="radio"
                        name="service_fee_rows[{{ $index }}][fee_direction]"
                        value="COLLECTED"
                        @checked($direction === 'COLLECTED')>
                    <span class="form-check-label">@lang('establishment::fields.service_fee_direction_collected')</span>
                </label>
                <label class="form-check form-check-custom form-check-solid">
                    <input class="form-check-input service-fee-direction"
                        type="radio"
                        name="service_fee_rows[{{ $index }}][fee_direction]"
                        value="PAID"
                        @checked($direction === 'PAID')>
                    <span class="form-check-label">@lang('establishment::fields.service_fee_direction_paid')</span>
                </label>
            </div>
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_gl_account')</label>
            <select name="service_fee_rows[{{ $index }}][fee_account_id]"
                class="form-select form-select-solid select-2-service-fee w-100"
                data-placeholder="@lang('messages.select')" data-allow-clear="true">
                <option value="">@lang('messages.select')</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($feeAccountId === (int) $account->id)>
                        {{ $accountLabel($account) }}
                    </option>
                @endforeach
            </select>
            <div class="form-text">@lang('establishment::fields.service_fee_gl_account_hint')</div>
        </div>
    </div>
</div>
