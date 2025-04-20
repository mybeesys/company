<div class="container">
    <form id="update-modules-form" method="POST" action="{{ route('update-modules') }}">
        @csrf
        <div class="row">
            @foreach ($modules as $key => $label)
                <div class="col-md-4 mb-4">
                    <div class="d-flex align-items-center gap-2" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                        <div class="form-check">
                            <input type="checkbox" name="modules[]" value="{{ $key }}"
                                id="module_{{ $key }}" class="form-check-input my-2"
                                style="border: 1px solid #9f9f9f;"
                                {{ in_array($key, $enabledModules ?? []) ? 'checked' : '' }}>
                        </div>
                        <label class="form-check-label" for="module_{{ $key }}">
                            @lang('general::general.' . $label)
                        </label>

                    </div>
                </div>
            @endforeach
        </div>
        <div class="separator d-flex flex-center m-5">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>
        <button type="submit" style="border-radius: 6px;" class="btn btn-primary w-200px">
            @lang('messages.save')
        </button>
    </form>
</div>
