<div class="container">
    <form id="update-currency" method="POST" action="{{ route('update-currency') }}">
        @csrf

        <div class="row my-5">
            <div class="col-4 mb-5">
                <div class="d-flex flex-column" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                    <label class="fs-6 fw-semibold mb-2">
                        @lang('general::general.currency')
                    </label>
                </div>
                <select class="form-select select-2  form-select-solid kt_ecommerce_select2_account"
                    style="padding: 0px 12px;border: 1px solid var(--bs-gray-300); width: 60% !important" name="currency"
                    id="currency">
                    @foreach ($currencies as $currency)
                        @if ($currency->currency_name_en)
                            <option value="{{ $currency->currency_symbol_en }}"
                                {{ $currency->currency_symbol_en  == $setting_currency ? 'selected' : '' }}>
                                @if (app()->getLocale() == 'ar')
                                    ( {{ $currency->name_ar }} )
                                @else
                                    ( {{ $currency->name_en }} )
                                @endif
                                - {{ $currency->currency_name_en }} ( {{ $currency->currency_symbol_en }} )
                            </option>
                        @endif
                    @endforeach


                </select>

            </div>
            <div class="separator d-flex flex-center m-5">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>


        </div>

        <button type="submit" style="border-radius: 6px;" class="btn btn-primary w-200px">
            @lang('messages.save')
        </button>
    </form>




</div>
