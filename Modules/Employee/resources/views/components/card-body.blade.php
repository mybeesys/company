@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'card-body pt-0 ' . $class]) }}>
    {{ $slot }}
</div>
