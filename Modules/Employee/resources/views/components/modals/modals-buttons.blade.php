<div class="text-center pt-6">
    <button type="reset" data-bs-toggle="modal" data-bs-target="#{{ $modal }}"
        class="btn btn-light me-3">@lang('employee::general.cancel')</button>
    <button type="submit" id="{{ $modal }}_submit" class="btn btn-primary">
        <span class="indicator-label">@lang('employee::general.save')</span>
        <span class="indicator-progress">@lang('employee::general.please_wait')
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
    </button>
</div>
