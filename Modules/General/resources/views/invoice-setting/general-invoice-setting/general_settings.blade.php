<form id="save-nots-terms" method="POST" action="{{ route('save-nots-terms') }}">
    @csrf
    <div class=" align-items-center  mb-5">
        <label class="fs-6 fw-semibold my-4 me-3">@lang('general::general.terms_and_conditions_en')</label>

        <textarea name="terms_and_conditions_en" id="terms_and_conditions_en">{{ $settings->where('key', 'terms_and_conditions_en')->first()->value ?? null }}</textarea>
    </div>

    <div class=" align-items-center  mb-5">
        <label class="fs-6 fw-semibold my-4 me-3">@lang('general::general.terms_and_conditions_ar')</label>

        <textarea name="terms_and_conditions_ar" id="terms_and_conditions_ar">{{ $settings->where('key', 'terms_and_conditions_ar')->first()->value ?? null }}</textarea>
    </div>

    <div class="row row-cols-lg-12 my-1 g-3">
        <label class="fs-6 fw-semibold my-2 me-3">@lang('general::general.note_en')</label>
        <div class="col-12">
            <div class="d-flex flex-column mb-4" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

                <textarea class="form-control form-control-solid " dir="ltr" rows="5" name="note_en">{{ $settings->where('key', 'note_en')->first()->value ?? null }}</textarea>
            </div>
        </div>
    </div>

    <div class="row row-cols-lg-12 my-1 g-3">
        <label class="fs-6 fw-semibold my-2 me-3">@lang('general::general.note_ar')</label>
        <div class="col-12">
            <div class="d-flex flex-column mb-4" dir="rtl">

                <textarea class="form-control form-control-solid" @if (app()->getLocale() == 'ar') dir="rtl" @endif rows="5"
                    name="note_ar">{{ $settings->where('key', 'note_ar')->first()->value ?? null }}</textarea>
            </div>
        </div>
    </div>
    <div class="separator d-flex flex-center m-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>
    <button type="submit" style="border-radius: 6px;" class="btn btn-primary w-200px">
        @lang('messages.save')
    </button>
</form>
