@extends('layouts.app')

@section('title', __('accounting::lang.add_journalEntry'))
@section('css')


@stop
@section('content')

    <div class="container">
        <div class="row" @if (app()->getLocale() == 'en') dir="rtl" @endif>
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <h1> @lang('accounting::lang.add_journalEntry')</h1>
            </div>
        </div>
    </div>

    <div class="separator d-flex flex-center my-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>


    <div class="row row-cols-lg-2 g-10"  @if (app()->getLocale() == 'ar') dir="rtl" @endif>
        <div class="col" >
            <div class="fv-row mb-9 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid">

                <label class="fs-6 fw-semibold mb-2 required">@lang('accounting::lang.journalEntry_date')</label>

                <input class="form-control form-control-solid required flatpickr-input" name="journalEntry_date"
                    placeholder="@lang('accounting::lang.Pick_journalEntry_date')" id="kt_calendar_datepicker_start_date" type="text"
                    data-gtm-form-interact-field-id="1">

                <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback"></div>
            </div>
        </div>
        <div class="col" data-kt-calendar="datepicker">
            <div class="fv-row mb-9">

                <label class="fs-6 fw-semibold mb-2">@lang('accounting::lang.ref_number')<span
                    class="fw-semibold mx-2 text-muted fs-7">@lang('accounting::lang.ref_number_note')</span> </label>

                <input class="form-control form-control-solid flatpickr-input" name="ref_number"
                     id="kt_calendar_datepicker_start_time" type="text"
                     data-gtm-form-interact-field-id="3">

            </div>
        </div>

    </div>




@stop

@section('script')

    <script>
        flatpickr("#kt_calendar_datepicker_start_date", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            defaultHour: 12,
            defaultMinute: 0
        });
    </script>

@stop
