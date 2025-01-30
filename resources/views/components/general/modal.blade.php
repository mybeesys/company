@props([
    'id',
    'title' => null,
    'class' => null,
    'header_class' => null,
    'body_class' => null,
    'header' => null,
    'module',
    'buttons' => true,
    'cancelButton' => true,
    'submitButton' => true,
    'form' => true
])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered {{ $class }}">
        <div class="modal-content">
            <div class="modal-header mb-2 {{ $header_class }}">
                @if ($title)
                    <h2 class="fw-bold">@lang($module . '::general.' . $title)</h2>
                @else
                    {{ $header }}
                @endif
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body mx-5 {{ $body_class }}">
                @if ($form)
                    <form id="{{ $id }}_form" class="form" action="#">
                @endif
                @csrf
                {{ $slot }}
                @if ($buttons)
                    <div class="text-center pt-5">
                        @if ($cancelButton)
                            <button type="reset" class="btn btn-light me-3"
                                data-bs-dismiss="modal">@lang('general.cancel')</button>
                        @endif
                        @if ($submitButton)
                            <button type="submit" class="btn btn-primary" data-kt-roles-modal-action="submit">
                                <span class="indicator-label">@lang('general.save')</span>
                            </button>
                        @endif
                    </div>
                @endif
                @if ($form)
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
