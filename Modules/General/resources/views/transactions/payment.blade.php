<form action="{{ route('add-payment') }}" method="POST">
    @csrf
<input type="hidden" name="id" value="{{$transaction->id}}">
    <div class="row row-cols-lg-12 my-1 g-10">

        <div class="col-4">
            <div class="fv-row ">

                <label class="fs-6 fw-semibold mb-2 required">@lang('accounting::lang.account')
                    <span class=" mt-2 px-1" data-bs-toggle="tooltip" title="@lang('sales::lang.payment_account_note')">
                        <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                    </span>
                </label>



                <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account " required
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
            <div class="d-flex flex-column  " @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                <label class="fs-6 fw-semibold mb-2 required">@lang('sales::lang.paid_amount')</label>

                <input class="form-control form-control-solid no-spin" required name="paid_amount"
                    value="{{ $amount }}" min="0" max="{{ $amount }}" placeholder="0.00"
                    id="paid_amount" type="number">
            </div>
        </div>

        <div class="col-4 pay-pament_on">
            <div class="fv-row  fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

                <label class="fs-6 fw-semibold mb-2 required">@lang('sales::lang.pament_on')</label>

                <input class="form-control form-control-solid" required name="pament_on"
                    value="{{ now()->format('Y-m-d\TH:i') }}" placeholder="@lang('sales::lang.pament_on')" id="pament_on"
                    type="datetime-local">
            </div>
        </div>


        <div class="col-4 pay-additionalNotes">
            <div class="d-flex flex-column mb-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                <label class="fs-6 fw-semibold mb-2">@lang('accounting::lang.additionalNotes')</label>

                <textarea class="form-control form-control-solid" rows="1" name="additionalNotes"></textarea>
            </div>
        </div>

    </div>

    <div class="col-12 d-flex justify-content-center">

        <button type="submit" class="btn btn-primary w-200px"
            @if (app()->getLocale() == 'ar') style="margin-right: 8px;" @endif>
            <span class="indicator-label">@lang('messages.submit')</span>
            <span class="indicator-progress">
                Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
            </span>
        </button>
    </div>
</form>
