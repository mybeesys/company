@props(['name', 'icon' => null, 'subMenuCount' => null])
@php
    $menuLabelKey = 'menuItemLang.' . $name;
    $menuLabelFallbackKey = 'lang.' . $name;
    $menuLabel = __($menuLabelKey);
    if ($menuLabel === $menuLabelKey && \Illuminate\Support\Facades\Lang::has($menuLabelFallbackKey)) {
        $menuLabel = __($menuLabelFallbackKey);
    }
@endphp
<span class="menu-link">
    @if (!$icon)
        <span class="menu-bullet">
            <span class="bullet bullet-dot"></span>
        </span>
    @else
        <span class="menu-icon">
            <i class='{{ $icon }}'></i>
        </span>
    @endif
    <span class="menu-title fs-6">{{ $menuLabel }}</span>
    @if ($subMenuCount)
        <span class="menu-arrow"></span>
    @endif
</span>
