<div class="voucher-view" dir="{{ !empty($voucherLocaleAr) ? 'rtl' : 'ltr' }}">
    @include('accounting::vouchers.partials.classic_voucher', ['forPrint' => false])

    <div class="d-flex {{ !empty($voucherLocaleAr) ? 'justify-content-start' : 'justify-content-end' }} gap-2 mt-4">
        @if (!empty($pdfUrl))
            <a href="{{ $pdfUrl }}" class="btn btn-light-danger" target="_blank">@lang('accounting::lang.download_pdf')</a>
        @endif
    </div>
</div>
