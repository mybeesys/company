<div class="row row-cols-lg-12 my-1 g-10">
    {{-- <div class="col-4">
        <div class="fv-row ">

            <label class="fs-6 fw-semibold mb-2 ">@lang('sales::lang.invoice_type') </label>

            <select name="invoice_type" id="invoice_type" class="form-select select-2 form-select-solid ">
                <option value="cash">@lang('sales::lang.cash')</option>
                <option value="due">@lang('sales::lang.due')</option>
            </select>
        </div>
    </div> --}}

    {{-- <div class="col-4">
        <div class="fv-row ">

            <label class="fs-6 fw-semibold mb-2 required">@lang('accounting::lang.cost_center') </label>

            <select class="form-select select-2 form-select-solid  kt_ecommerce_select2_cost_center" name="cost_center"
                id="cost_center">
                <option value=""></option>

                @foreach ($cost_centers as $cost_center)
                    <option value="{{ $cost_center->id }}">
                        @if (app()->getLocale() == 'ar')
                            {{ $cost_center->name_ar }} - <span class="fw-semibold mx-2 text-muted fs-7">
                                {{ $cost_center->account_center_number }}</span>
                        @else
                            {{ $cost_center->name_en }} - <span
                                class="fw-semibold mx-2 text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                        @endif
                    </option>
                @endforeach


            </select>
        </div>
    </div> --}}

    <div class="col-4">
        <div class="fv-row ">

            <label class="fs-6 fw-semibold mb-2 ">@lang('accounting::lang.account')
                <span class=" mt-2 px-1" data-bs-toggle="tooltip" title="@lang('sales::lang.payment_account_note')">
                    <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                </span> </label>



            <select  class="form-select select-2  form-select-solid kt_ecommerce_select2_account "
                name="account_id" id="account_id">

                <option value="">@lang('sales::lang.payment_account_select')</option>

                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">
                        @if (app()->getLocale() == 'ar')
                            {{ $account->name_ar }} - <span
                                class="fw-semibold mx-2 text-muted fs-5">@lang('accounting::lang.' . $account->account_primary_type)</span>
                        @else
                            {{ $account->name_en }} - <span
                                class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.' . $account->account_primary_type)</span>
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-4 pay-paid_amount">
        <div class="d-flex flex-column " @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('sales::lang.paid_amount')</label>

            <input class="form-control form-control-solid no-spin" name="paid_amount"  value=""
                placeholder="0.00" id="paid_amount" type="number">
        </div>
    </div>

    <div class="col-4 pay-pament_on">
        <div class="fv-row  fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

            <label class="fs-6 fw-semibold mb-2 ">@lang('sales::lang.pament_on')</label>

            <input class="form-control form-control-solid" name="pament_on"
                value="{{ now()->format('Y-m-d\TH:i') }}" placeholder="@lang('sales::lang.pament_on')" id="pament_on"
                type="datetime-local">
        </div>
    </div>
    {{-- <div class="col-4 pay-payment_type">
        <div class="fv-row ">

            <label class="fs-6 fw-semibold mb-2 required">@lang('sales::lang.payment_type') </label>
            <select name="payment_type" id="payment_type" class="form-select select-2 form-select-solid ">
                @foreach ($paymentMethods as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div> --}}




    {{-- @include('sales::sell.create.card-payment') --}}
    {{-- @include('sales::sell.create.check') --}}
    {{-- @include('sales::sell.create.bank_transfer') --}}


    <div class="col-4 pay-additionalNotes">
        <div class="d-flex flex-column mb-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            <label class="fs-6 fw-semibold mb-2">@lang('accounting::lang.additionalNotes')</label>

            <textarea class="form-control form-control-solid" rows="1" name="additionalNotes"></textarea>
        </div>
    </div>

</div>
