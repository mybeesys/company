@php
    use Modules\Sales\Services\InvoiceServiceFeeAmounts;
    $visibleFees = InvoiceServiceFeeAmounts::visibleOnDocuments(
        InvoiceServiceFeeAmounts::payloadFromTransaction($transaction)
    );
    $locale = app()->getLocale();
@endphp
@if ($visibleFees !== [])
    @if (count($visibleFees) > 1)
        <p class="fs-5 fw-bold mb-1">@lang('sales::lang.service_fees'):</p>
        @foreach ($visibleFees as $feeLine)
            @php
                $feeName = $locale === 'ar'
                    ? ($feeLine['name_ar'] ?? $feeLine['name_en'] ?? __('sales::lang.service_fees'))
                    : ($feeLine['name_en'] ?? $feeLine['name_ar'] ?? __('sales::lang.service_fees'));
                $feeTotal = (float) ($feeLine['fee_amount'] ?? 0) + (float) ($feeLine['tax_amount'] ?? 0);
            @endphp
            <p class="fs-6 ms-3 mb-0">{{ $feeName }}: (+) {{ number_format($feeTotal, 2) }}</p>
        @endforeach
    @else
        @php
            $feeLine = $visibleFees[0];
            $feeName = $locale === 'ar'
                ? ($feeLine['name_ar'] ?? $feeLine['name_en'] ?? __('sales::lang.service_fees'))
                : ($feeLine['name_en'] ?? $feeLine['name_ar'] ?? __('sales::lang.service_fees'));
            $feeTotal = (float) ($feeLine['fee_amount'] ?? 0) + (float) ($feeLine['tax_amount'] ?? 0);
        @endphp
        <p class="fs-5">{{ $feeName }}: (+) {{ number_format($feeTotal, 2) }}</p>
    @endif
@endif
