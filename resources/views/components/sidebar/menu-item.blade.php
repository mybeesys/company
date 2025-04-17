@props(['url', 'name'])
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
        <span class="menu-title fs-7 "  aria-hidden="true"></i>{{ __('menuItemLang.' . $name) }}</span>
    </a>
</div>
