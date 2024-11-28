@props(['cancelUrl' => '/', 'disabled' => false, 'id' => '', 'class' => null])
<div class="d-flex justify-content-end {{ $class }}">
    <a href="{{ $cancelUrl }}" class="btn btn-light me-5">@lang('general.cancel')</a>
    <button type="submit" id="{{ $id }}_button" class="btn btn-primary" @disabled($disabled)>
        <span class="indicator-label">@lang('general.save')</span>
        <span class="indicator-progress">@lang('general.please_wait')
            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
    </button>
</div>
