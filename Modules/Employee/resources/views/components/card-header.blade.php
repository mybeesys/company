@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'card-header ' . $class]) }}>
    {{ $slot }}
</div>
