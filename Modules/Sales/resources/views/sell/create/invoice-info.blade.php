<div class="card" data-section="contact" style="border: 0;box-shadow: none">
    <div class="container">
        <div class="d- align-items-center mb-5" @if ($quotation) style="display: none;" @endif>
            {{-- <span class=" mt-2" data-bs-toggle="tooltip" title="@lang('accounting::lang.ref_number_note')">
                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('sales::fields.invoice_no')

                </label>
            </span>
            <span class=" " style="width: 100%;min-width: 100%;" data-bs-toggle="tooltip" title="@lang('accounting::lang.ref_number_note')">

            <input class="form-control form-control-solid custom-height" name="invoice_no"
                placeholder="@lang('sales::fields.invoice_no')" id="invoice_no" type="text">
            </span> --}}
            <label class="fs-6 fw-semibold mb-2 " style="width: 160px;">@lang('sales::lang.invoice_type') </label>

            <select name="invoice_type" id="invoice_type" style="padding: 7px;width: 60%!important"
                class="form-select select-2 form-select-solid ">
                <option value="cash">@lang('sales::lang.cash')</option>
                <option value="due">@lang('sales::lang.due')</option>
            </select>

        </div>

        {{-- <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;">@lang('sales::fields.issue_date')</label>
            <input class="form-control form-control-solid custom-height" name="issue_date" required
                value="{{ now()->format('Y-m-d') }}" placeholder="@lang('sales::fields.issue_date')" id="issue_date" type="date">
        </div> --}}
        {{-- <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('sales::fields.payment_terms')</label>
            <select name="payment_terms" id="payment_terms" style="padding: 7px;width: 60%!important" class="form-select select-2 form-select-solid ">
                @foreach ($payment_terms as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div> --}}


        <div class="d-flex align-items-center mb-5">

            @if ($quotation)
                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;">@lang('sales::fields.issue_date')</label>
            @else
                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;">@lang('sales::fields.transaction_date')</label>
            @endif
            <input class="form-control form-control-solid custom-height" name="transaction_date"
                @if ($transaction?->transaction_date) value="{{ $transaction->transaction_date }}" @endif
                value="{{ now()->format('Y-m-d') }}" required placeholder="@lang('sales::fields.transaction_date')" id="transaction_date"
                type="date">
        </div>



        <div class="d-flex align-items-center mb-5">

            @if ($quotation)
                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;">@lang('sales::fields.Expiry Date')</label>
            @else
                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;">@lang('sales::fields.due_date')</label>
            @endif
            <input class="form-control form-control-solid custom-height" name="due_date" required
            @if ($transaction?->due_date) value="{{ $transaction->due_date }}" @endif
            value="{{ now()->format('Y-m-d') }}" placeholder="@lang('sales::fields.due_date')" id="due_date" type="date">
        </div>



        <div class="d-flex align-items-center mb-5">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('sales::fields.notice')</label>
            <input class="form-control form-control-solid custom-height" name="notice"
            @if ($transaction?->notice) value="{{ $transaction->notice }}" @endif
            value=""
                placeholder="@lang('sales::fields.notice')" id="notice" type="text">
        </div>


        <div class="align-items-center mb-5" id="dev-costCenter" style="display: none">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('accounting::lang.cost_center')</label>
            <select class="form-select select-2 form-select-solid  kt_ecommerce_select2_cost_center" name="cost_center"
                id="cost_center" style="width: 60%!important">
                <option value=""></option>

                @foreach ($cost_centers as $cost_center)
                    <option value="{{ $cost_center->id }}" @if ($transaction?->cost_center == $cost_center->id) selected @endif>
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

        <div class=" align-items-center mb-5" id="div-Delegates" style="display: none">
            <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('sales::lang.Delegates')</label>
            <select class="form-select select-2 form-select-solid  Delegates" name="Delegates" id="Delegates"
                style="width: 60%!important">
                <option value=""></option>

                {{-- @foreach ($cost_centers as $cost_center)
                    <option value="{{ $cost_center->id }}">
                        @if (app()->getLocale() == 'ar')
                            {{ $cost_center->name_ar }} - <span class="fw-semibold mx-2 text-muted fs-7">
                                {{ $cost_center->account_center_number }}</span>
                        @else
                            {{ $cost_center->name_en }} - <span
                                class="fw-semibold mx-2 text-muted fs-7">{{ $cost_center->account_center_number }}</span>
                        @endif
                    </option>
                @endforeach --}}

            </select>
        </div>

    </div>
</div>
