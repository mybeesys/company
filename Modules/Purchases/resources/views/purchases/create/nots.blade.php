<div class="row row-cols-lg-12 my-1 g-10">
    <div class="col-12">
        <div class="d-flex flex-column mb-4" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            {{-- <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.invoice_note')</label> --}}

            <textarea class="form-control form-control-solid" rows="5" name="invoice_note"></textarea>
        </div>
    </div>
</div>
