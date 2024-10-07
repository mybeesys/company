<div class="modal fade" id="kt_modal_addingJournalEntryParty" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-900px">


        <div class="modal-content" data-select2-id="select2-data-139-qxh8">

            <div class="modal-header" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

                <h2>@lang('accounting::lang.Adding Journal Entry Party')</h2>

                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>

            </div>

            <div class="modal-body py-lg-10 px-lg-10" dir="ltr">

                <div class="stepper stepper-pills stepper-column d-flex flex-column flex-xl-row flex-row-fluid first"
                    id="kt_modal_create_app_stepper" data-kt-stepper="true">

                    <div class="d-flex justify-content-center justify-content-xl-start flex-row-auto w-100 w-xl-300px">

                        <div class="stepper-nav ps-lg-10">


                            {{-- <div class="stepper-item pending" data-kt-stepper-element="nav">
                                <!--begin::Wrapper-->
                                <div class="stepper-wrapper">
                                    <!--begin::Icon-->
                                    <div class="stepper-icon w-40px h-40px">
                                        <i class="ki-outline ki-check stepper-check fs-2"></i> <span
                                            class="stepper-number">2</span>
                                    </div>
                                    <!--begin::Icon-->

                                    <!--begin::Label-->
                                    <div class="stepper-label">
                                        <h3 class="stepper-title">
                                            Frameworks
                                        </h3>

                                        <div class="stepper-desc">
                                            Define your app framework
                                        </div>
                                    </div>
                                    <!--begin::Label-->
                                </div>
                                <!--end::Wrapper-->

                                <!--begin::Line-->
                                <div class="stepper-line h-40px"></div>
                                <!--end::Line-->
                            </div>
                             --}}

                        </div>

                    </div>



                    <div class="flex-row-fluid py-lg-5 px-lg-15" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

                        <form class="form fv-plugins-bootstrap5 fv-plugins-framework" novalidate="novalidate"
                            id="kt_modal_create_app_form" data-gtm-form-interact-id="1"
                            data-select2-id="select2-data-kt_modal_create_app_form">

                            <div class="current" data-kt-stepper-element="content">
                                <div class="w-100">

                                    <div class="fv-row mb-10 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

                                        <label class="d-flex align-items-center fs-5 fw-semibold mb-2">
                                            <span class="required">@lang('accounting::lang.account')</span>
                                        </label>



                                        <select id="kt_ecommerce_select2_account" required
                                            class="form-select select-2 form-select-solid select-2" name="account_id">
                                             
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


                                    <div class="fv-row mb-10 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

                                        <label class="d-flex align-items-center fs-5 fw-semibold mb-2">
                                            <span class="">@lang('accounting::lang.cost_center')</span>
                                        </label>


                                        <select id="kt_ecommerce_select2_cost_center"
                                            class="form-select select-2 form-select-solid select-2" name="cost_center">

                                                @foreach ($cost_centers as $cost_center)
                                                    <option value="{{ $cost_center->id }}">
                                                        @if (app()->getLocale() == 'ar')
                                                            {{ $cost_center->name_ar }} - <span
                                                                class="fw-semibold mx-2 text-muted fs-7"> {{$cost_center->account_center_number}}</span>
                                                        @else
                                                            {{ $cost_center->name_en }} - <span
                                                                class="fw-semibold mx-2 text-muted fs-7">{{$cost_center->account_center_number}}</span>
                                                        @endif
                                                    </option>
                                                @endforeach


                                        </select>
                                    </div>



                                    <div class="d-flex flex-column mb-8">
                                        <label class="fs-6 fw-semibold mb-2">@lang('accounting::lang.additionalNotes')</label>

                                        <textarea class="form-control form-control-solid" rows="3" id="notes" name="notes"></textarea>
                                    </div>


                                    <div class="fv-row">



                                        <div class="fv-row fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

                                            <label class="d-flex flex-stack mb-5 cursor-pointer">

                                                <span class="d-flex align-items-center me-2  ">

                                                    <span class="symbol symbol-50px me-6">
                                                        <span class="symbol-label bg-light-danger">
                                                            <i class="fas fa-user-minus fs-1 text-danger"></i>
                                                        </span>
                                                    </span>



                                                    <span
                                                        class="d-flex flex-column @if (app()->getLocale() == 'ar') px-4 @endif">
                                                        <span class="fw-bold fs-6">@lang('accounting::lang.debit')</span>

                                                        <span class="fs-7 text-muted">@lang('accounting::lang.Expected future receivables')</span>
                                                    </span>

                                                </span>

                                                <input type="number"
                                                    class="form-control form-control-lg form-control-solid"
                                                    name="debit" id="debit" placeholder="0.0" value=""
                                                    style="width: 107px;" data-gtm-form-interact-field-id="4">

                                            </label>

                                            <label class="d-flex flex-stack mb-5 cursor-pointer">

                                                <span class="d-flex align-items-center me-2">

                                                    <span class="symbol symbol-50px me-6">
                                                        <span class="symbol-label bg-light-success">
                                                            <i class="fas fa-user-plus fs-1 text-success"></i>
                                                        </span>
                                                    </span>

                                                    <span
                                                        class="d-flex flex-column @if (app()->getLocale() == 'ar') px-4 @endif">
                                                        <span class="fw-bold fs-6">@lang('accounting::lang.credit')</span>

                                                        <span class="fs-7 text-muted">@lang('Liabilities that must be paid')</span>
                                                    </span>

                                                </span>

                                                <input type="number"
                                                    class="form-control form-control-lg form-control-solid"
                                                    name="credit" id="credit" placeholder="0.0" value=""
                                                    style="width: 107px;" data-gtm-form-interact-field-id="4">

                                            </label>

                                        </div>

                                    </div>

                                </div>
                            </div>

                        </form>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addJournalEntry">@lang('messages.add')</button>
                </div>

            </div>

        </div>

    </div>

</div>
