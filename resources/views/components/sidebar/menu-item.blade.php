@props(['url', 'name'])
@php
    $menuLabelKey = 'menuItemLang.' . $name;
    $menuLabelFallbackKey = 'lang.' . $name;
    $menuLabel = __($menuLabelKey);
    if ($menuLabel === $menuLabelKey && \Illuminate\Support\Facades\Lang::has($menuLabelFallbackKey)) {
        $menuLabel = __($menuLabelFallbackKey);
    }
@endphp
<div class="menu-item">
    <a @class([
        'menu-link',
        'active' =>
            request()->is($url) ||
            request()->is($url . '/*'),
    ]) href='/{{ $url }}'>
        <span class="menu-bullet">
            <span class="bullet bullet-dot"></span>
        </span>
        <span class="menu-title fs-7" aria-hidden="true">{{ $menuLabel }}</span>
    </a>
</div>
