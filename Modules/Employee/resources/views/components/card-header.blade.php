@props(['class' => '', 'id' => '', 'collapsible' => false])
<div {{ $attributes->merge(['class' => 'card-header ' . $class]) }} data-bs-toggle="{{ $collapsible ? 'collapse' : '' }}"
    data-bs-target="#{{ $id }}">
    {{ $slot }}
</div>
