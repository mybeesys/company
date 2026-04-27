@props(['url', 'name', 'icon'])
@php
    $menuLabelKey = 'menuItemLang.' . $name;
    $menuLabelFallbackKey = 'lang.' . $name;
    $menuLabel = __($menuLabelKey);
    if ($menuLabel === $menuLabelKey && \Illuminate\Support\Facades\Lang::has($menuLabelFallbackKey)) {
        $menuLabel = __($menuLabelFallbackKey);
    }
@endphp

<span class="menu-item">
    <a @class([
        'menu-link',
        'active' => request()->is($url) || request()->is($url . '/*'),
    ]) href='{{ $url }}'>

        @if (!$icon)
            <span class="menu-bullet">
                <span class="bullet bullet-dot"></span>
            </span>
        @else
            <span class="menu-icon">
                <i style="color: #99a1b7" class='{{ $icon }}'></i>
            </span>
        @endif
        <span class="menu-title fs-6">{{ $menuLabel }}</span>
    </a>
</span>
