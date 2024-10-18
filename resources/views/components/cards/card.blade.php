@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'card card-flush ' . $class]) }}>
    {{ $slot }}
</div>
