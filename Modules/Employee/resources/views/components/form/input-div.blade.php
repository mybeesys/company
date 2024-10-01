@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'fv-row flex-md-root ' . $class]) }}>
    {{ $slot }}
</div>
