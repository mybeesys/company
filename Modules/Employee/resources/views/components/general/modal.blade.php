@props(['id', 'title' => null, 'class' => null, 'header_class' => null, 'body_class' => null, 'title' => null, 'header' => null])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $class }}">
        <div class="modal-content">
            <div class="modal-header mb-2 {{ $header_class }}">
                @if ($title)
                    <h2 class="fw-bold">@lang('employee::general.' . $title)</h2>
                @else
                    {{ $header }}
                @endif
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body mx-5 {{ $body_class }}">
                <form id="{{ $id }}_form" class="form" action="#">
                    {{ $slot }}
                    <div class="text-center pt-5">
                        <button type="reset" class="btn btn-light me-3"
                            data-bs-dismiss="modal">@lang('general.cancel')</button>
                        <button type="submit" class="btn btn-primary" data-kt-roles-modal-action="submit">
                            <span class="indicator-label">@lang('general.save')</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
