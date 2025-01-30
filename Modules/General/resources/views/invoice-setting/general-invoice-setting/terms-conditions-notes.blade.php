<div class="my-3 form-check">
    <input type="checkbox" id="toggle_terms_notes" name="toggle_terms_notes" class="form-check-input">
    <label for="toggle_terms_notes" class="fs-6 fw-semibold">@lang('general::general.note') / @lang('general::general.terms_and_conditions')</label>
</div>

<div id="terms_notes_section" style="display: none;">
    <div class="separator d-flex flex-center m-5">
        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
    </div>

    <div class="align-items-center">
        @php
            $termsKey = app()->getLocale() == 'en' ? 'terms_and_conditions_en' : 'terms_and_conditions_ar';
            $termsValue = $settings->where('key', $termsKey)->first()->value ?? null;
        @endphp

        @if (!empty($termsValue))
            <label class="fs-6 fw-semibold me-3">@lang('general::general.terms_and_conditions'):</label>
            <label class="fs-5 fw-semibold my-2 me-3">{!! $termsValue !!}</label>
        @endif

        <input type="hidden" name="terms_and_conditions_en" value="{{ $settings->where('key', 'terms_and_conditions_en')->first()->value ?? null }}">
        <input type="hidden" name="terms_and_conditions_ar" value="{{ $settings->where('key', 'terms_and_conditions_ar')->first()->value ?? null }}">
    </div>

    <div class="align-items-center mb-2">
        @php
            $noteKey = app()->getLocale() == 'en' ? 'note_en' : 'note_ar';
            $noteValue = $settings->where('key', $noteKey)->first()->value ?? null;
        @endphp

        @if (!empty($noteValue))
            <label class="fs-6 fw-semibold my-2 me-3">@lang('general::general.note'):</label>
            <label class="fs-5 fw-semibold me-3">{{ $noteValue }}</label>
        @endif

        <input type="hidden" name="note_en" value="{{ $settings->where('key', 'note_en')->first()->value ?? null }}">
        <input type="hidden" name="note_ar" value="{{ $settings->where('key', 'note_ar')->first()->value ?? null }}">
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let checkbox = document.getElementById("toggle_terms_notes");
        let section = document.getElementById("terms_notes_section");

        checkbox.addEventListener("change", function() {
            section.style.display = this.checked ? "block" : "none";
        });
    });
</script>
