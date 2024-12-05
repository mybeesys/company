<div class="row row-cols-lg-12 my-1 g-10">

    <div class="col-4">
        <div class="d-flex flex-column mb-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.shipping_details')</label>

            <textarea class="form-control form-control-solid" rows="1" name="shipping_details"></textarea>
        </div>
    </div>

    <div class="col-4">
        <div class="d-flex flex-column mb-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.shipping_address')</label>

            <textarea class="form-control form-control-solid" rows="1" name="shipping_address"></textarea>
        </div>
    </div>


    <div class="col-4 pay-paid_amount">
        <div class="d-flex flex-column " @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.shipping_fees')</label>

            <input class="form-control form-control-solid no-spin" name="shipping_fees" required value=""
                placeholder="0.00" id="shipping_fees" type="number">
        </div>
    </div>

    <div class="col-4 pay-payment_type">
        <div class="fv-row ">

            <label class="fs-6 fw-semibold mb-2 ">@lang('sales::lang.shipping_status') </label>
            <select name="shipping_status" id="shipping_status" class="form-select select-2 form-select-solid ">
                @foreach ($orderStatuses as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-4 pay-paid_amount">
        <div class="d-flex flex-column " @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.delivered_to')</label>

            <input class="form-control form-control-solid no-spin" name="delivered_to" required value=""
                placeholder="@lang('sales::lang.delivered_to')" id="delivered_to" type="text">
        </div>
    </div>

    <div class="col-4 pay-paid_amount">
        <div class="d-flex flex-column " @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.delivery_man')</label>

            <input class="form-control form-control-solid no-spin" name="delivery_man" required value=""
                placeholder="@lang('sales::lang.delivery_man')" id="delivery_man" type="text">
        </div>
    </div>






</div>
