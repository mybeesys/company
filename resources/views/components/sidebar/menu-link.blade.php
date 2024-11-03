@props(['name', 'icon' => null])
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
    <span class="menu-title">{{ __('menuItemLang.' . $name) }}</span>
    @if (!$icon)
        <span class="menu-arrow"></span>
    @endif
</span>
