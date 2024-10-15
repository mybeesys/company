@props(['class' => 'null'])
<div {{ $attributes->merge(['class' => 'form-check form-switch form-check-success form-check-solid ' . $class]) }}>
{{ $slot }}
</div>