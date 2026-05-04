@props(['url', 'name', 'icon'])
@php
    $menuLabelKey = 'menuItemLang.' . $name;
    $menuLabelFallbackKey = 'lang.' . $name;
    $menuLabel = __($menuLabelKey);
    if ($menuLabel === $menuLabelKey && \Illuminate\Support\Facades\Lang::has($menuLabelFallbackKey)) {
        $menuLabel = __($menuLabelFallbackKey);
    }
    $path = ($url !== null && $url !== '') ? ltrim($url, '/') : '';
    $href = $path !== '' ? '/'.$path : '#';
@endphp

<span class="menu-item">
    <a @class([
        'menu-link',
        'active' => $path !== '' && (request()->is($path) || request()->is($path.'/*')),
    ]) href="{{ $href }}">

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
