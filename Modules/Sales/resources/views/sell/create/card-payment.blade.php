<div class="row row-cols-lg-12 my-5 mx-2" id="card" style="display: none">
    <div class="col-3">
        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.card_number')</label>
            <input class="form-control form-control-solid" name="card_number" required placeholder="911*********2266"
                value="" type="text">
        </div>
    </div>
    <div class="col-3">
        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.card_type')</label>
            <select class="form-select form-select-solid" name="card_type" required>
                <option value="Creditcard" selected>Credit Card</option>
                <option value="Debitcard" selected>Debit Card</option>
                <option value="visa">Visa</option>
                <option value="mastercard">Master Card</option>
            </select>
        </div>
    </div>
    <div class="col-3">
        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.cardholder_name')</label>
            <input class="form-control form-control-solid" name="cardholder_name" required
                placeholder="@lang('sales::lang.cardholder_name_placeholder')" type="text">
        </div>
    </div>
    <div class="col-3">
        <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.transaction_id')</label>
            <input class="form-control form-control-solid" name="transaction_id" placeholder="@lang('sales::lang.transaction_id_placeholder')"
                type="text">
        </div>
    </div>
    <div class="col-3">
        <div class="d-flex flex-column my-5" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.expiry_month')</label>
            <input class="form-control form-control-solid" name="expiry_month" placeholder="@lang('sales::lang.month_placeholder')"
                type="text">
        </div>
    </div>
    <div class="col-3">
        <div class="d-flex flex-column my-5" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.expiry_year')</label>
            <input class="form-control form-control-solid" name="expiry_year" placeholder="@lang('sales::lang.year_placeholder')"
                type="text">
        </div>
    </div>
    <div class="col-3">
        <div class="d-flex flex-column my-5" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.security_code')</label>
            <input class="form-control form-control-solid" name="security_code" placeholder="@lang('sales::lang.security_code_placeholder')"
                type="text">
        </div>
    </div>
</div>
