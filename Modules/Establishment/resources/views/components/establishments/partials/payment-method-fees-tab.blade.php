{{--
  تاب رسوم طريقة الدفع
  المتغيرات: $index (int), $row (array مع مفتاح fees[])
--}}
@php
    $fees = $row['fees'] ?? [];
    $locale = $locale ?? app()->getLocale();
@endphp
<div class="payment-method-fees-pane" data-fees-pane data-index="{{ $index }}">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fs-6 fw-bold mb-1">@lang('establishment::fields.payment_method_fees')</h5>
            <p class="text-muted fs-8 mb-0">@lang('establishment::fields.payment_method_fees_hint')</p>
        </div>
        <button type="button"
            class="btn btn-sm btn-light-primary pmf-add-fee-btn"
            data-index="{{ $index }}">
            <i class="ki-outline ki-plus fs-5"></i>
            @lang('establishment::fields.payment_method_add_fee')
        </button>
    </div>

    <div class="d-flex flex-column gap-3 pmf-fees-list" data-index="{{ $index }}">
        @forelse ($fees as $feeIndex => $fee)
            @include('establishment::components.establishments.partials.payment-method-fee-row', [
                'methodIndex' => $index,
                'feeIndex'    => $feeIndex,
                'fee'         => $fee,
                'locale'      => $locale,
            ])
        @empty
            <p class="text-muted fs-8 pmf-empty-hint">@lang('establishment::fields.payment_method_fees_empty')</p>
        @endforelse
    </div>
</div>
