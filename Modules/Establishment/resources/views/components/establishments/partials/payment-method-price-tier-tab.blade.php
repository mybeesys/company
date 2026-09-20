@php
    $selectedPriceTierId = $row['price_tier_id'] ?? null;
    $priceTierOptions = $priceTierOptions ?? collect();
@endphp
<div class="payment-method-price-tier-pane">
    <div class="d-flex flex-column gap-3">
        <div>
            <div class="fw-bold fs-6 text-gray-800">@lang('establishment::fields.payment_method_price_tier')</div>
            <div class="text-muted fs-7 mt-1">@lang('establishment::fields.payment_method_price_tier_hint')</div>
        </div>

        <div class="fv-row">
            <label class="form-label fw-semibold mb-2">@lang('establishment::fields.payment_method_price_tier_select')</label>
            <select class="form-select form-select-solid select-2-cashier"
                name="cashier_payment_rows[{{ $index }}][price_tier_id]"
                data-placeholder="@lang('messages.select')"
                data-allow-clear="true">
                <option value="">@lang('messages.select')</option>
                @foreach ($priceTierOptions as $tier)
                    <option value="{{ $tier->id }}" @selected((int) $selectedPriceTierId === (int) $tier->id)>
                        {{ $locale === 'ar' ? ($tier->name_ar ?: $tier->name_en) : ($tier->name_en ?: $tier->name_ar) }}
                    </option>
                @endforeach
            </select>
            <div class="form-text text-muted">
                @lang('establishment::fields.payment_method_price_tier_source_hint')
            </div>
        </div>
    </div>
</div>
