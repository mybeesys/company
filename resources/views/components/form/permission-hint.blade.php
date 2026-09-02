@props([
    'title' => '',
    'body' => '',
    'placement' => 'auto',
])

@if ($body !== '')
    <button type="button"
        class="ems-perm-hint"
        data-ems-perm-hint="1"
        data-bs-toggle="popover"
        data-bs-trigger="hover focus"
        data-bs-placement="{{ $placement }}"
        data-bs-custom-class="ems-perm-popover"
        data-bs-html="true"
        data-bs-title="{{ e($title) }}"
        data-bs-content="{{ e($body) }}"
        aria-label="{{ e($title) }}"
        onclick="event.preventDefault(); event.stopPropagation();"
        onmousedown="event.stopPropagation();">
        <i class="ki-outline ki-information-2"></i>
    </button>
@endif
