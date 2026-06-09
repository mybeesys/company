@props(['url', 'name'])
@php
    $menuLabelKey = 'menuItemLang.' . $name;
    $menuLabelFallbackKey = 'lang.' . $name;
    $menuLabel = __($menuLabelKey);
    if ($menuLabel === $menuLabelKey && \Illuminate\Support\Facades\Lang::has($menuLabelFallbackKey)) {
        $menuLabel = __($menuLabelFallbackKey);
    }

    $menuPath = strtok(ltrim($url, '/'), '?') ?: '';
    $menuQueryString = str_contains($url, '?') ? parse_url($url, PHP_URL_QUERY) : '';
    parse_str($menuQueryString ?: '', $menuQuery);
    $menuTab = $menuQuery['tab'] ?? null;

    $menuIsActive = request()->is($menuPath) || request()->is($menuPath.'/*');
    if ($menuIsActive && $menuTab !== null) {
        $menuIsActive = request()->query('tab') === $menuTab;
    }
@endphp
<div class="menu-item">
    <a @class([
        'menu-link',
        'active' => $menuIsActive,
    ]) href='/{{ $url }}'>
        <span class="menu-bullet">
            <span class="bullet bullet-dot"></span>
        </span>
        <span class="menu-title fs-7" aria-hidden="true">{{ $menuLabel }}</span>
    </a>
</div>
