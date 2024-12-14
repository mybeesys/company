<div class="row row-cols-lg-12 mt-5 " id="bank_transfer" style="display: none">
    <div class="col-6">
        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.bank_transfer_number')</label>
            <input class="form-control form-control-solid" name="bank_transfer_number" required placeholder="@lang('sales::lang.bank_transfer_number')"
                value="" type="text">
        </div>
    </div>
</div>
