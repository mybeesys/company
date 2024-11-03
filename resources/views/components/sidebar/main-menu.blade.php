@props(['isSubmenuActive'])
<div data-kt-menu-trigger="click" @class(['menu-item menu-accordion', 'show' => $isSubmenuActive])>
    {{ $slot }}
</div>
