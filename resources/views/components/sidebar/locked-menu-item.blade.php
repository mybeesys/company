@props([
    'name',
    'upgradeUrl',
    'hint' => null,
])
@php
    $menuLabelKey = 'menuItemLang.'.$name;
    $menuLabelFallbackKey = 'lang.'.$name;
    $menuLabel = __($menuLabelKey);
    if ($menuLabel === $menuLabelKey && \Illuminate\Support\Facades\Lang::has($menuLabelFallbackKey)) {
        $menuLabel = __($menuLabelFallbackKey);
    }
    $hint = $hint ?: __('general::general.entitlement_upgrade_hint');
    $cta = __('general::general.entitlement_upgrade_cta');
@endphp

<div class="menu-item">
    <a href="{{ $upgradeUrl }}"
       class="menu-link entitlement-locked-link"
       data-bs-toggle="tooltip"
       data-bs-placement="left"
       data-bs-custom-class="entitlement-upgrade-tooltip"
       title="{{ $cta }} — {{ $hint }}"
       aria-label="{{ $cta }}: {{ $menuLabel }}">
        <span class="menu-bullet entitlement-locked-icon">
            <span class="bullet bullet-dot"></span>
            <span class="entitlement-locked-badge" aria-hidden="true"></span>
        </span>
        <span class="menu-title fs-7 entitlement-locked-title">{{ $menuLabel }}</span>
    </a>
</div>
