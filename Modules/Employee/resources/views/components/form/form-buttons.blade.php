@props(['cancelUrl' => '/'])
<div class="d-flex justify-content-end">
    <a href="{{ $cancelUrl }}" class="btn btn-light me-5">@lang('employee::general.cancel')</a>
    <button type="submit" class="btn btn-primary">
        <span class="indicator-label">@lang('employee::general.save')</span>
        <span class="indicator-progress">@lang('employee::general.please_wait')
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
    </button>
</div>
