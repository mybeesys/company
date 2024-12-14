<div class="row row-cols-lg-12 mt-5 " id="bank_check" style="display: none">
    <div class="col-6">
        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.check_number')</label>
            <input class="form-control form-control-solid" name="check_number" required placeholder="@lang('sales::lang.check_number')"
                value="" type="text">
        </div>
    </div>
</div>
