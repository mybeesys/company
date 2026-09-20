@php
    $accounts = $accounts ?? collect();
    $locale = $locale ?? app()->getLocale();
    $debitId = (int) ($row['debit_accounting_account_id'] ?? 0);
    $creditId = (int) ($row['credit_accounting_account_id'] ?? 0);
@endphp
<div class="service-fee-accounting-pane">
    <div class="mb-4">
        <div class="fw-bold fs-6 text-gray-800">@lang('establishment::general.service_fee_accounting_title')</div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_debit_account')</label>
            <select name="service_fee_rows[{{ $index }}][debit_accounting_account_id]"
                class="form-select form-select-solid select-2-service-fee w-100"
                data-placeholder="@lang('messages.select')" data-allow-clear="true">
                <option value="">@lang('messages.select')</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($debitId === (int) $account->id)>
                        ({{ $account->gl_code }})
                        {{ $locale === 'ar' ? ($account->name_ar ?: $account->name_en) : ($account->name_en ?: $account->name_ar) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">@lang('establishment::fields.service_fee_credit_account')</label>
            <select name="service_fee_rows[{{ $index }}][credit_accounting_account_id]"
                class="form-select form-select-solid select-2-service-fee w-100"
                data-placeholder="@lang('messages.select')" data-allow-clear="true">
                <option value="">@lang('messages.select')</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($creditId === (int) $account->id)>
                        ({{ $account->gl_code }})
                        {{ $locale === 'ar' ? ($account->name_ar ?: $account->name_en) : ($account->name_en ?: $account->name_ar) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>
