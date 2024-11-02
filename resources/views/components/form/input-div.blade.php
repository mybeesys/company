@props(['class' => '', 'row' => true, 'attribute' => null])
<div @class(['fv-row', 'flex-md-root' => $row, $class]) {{ $attribute }}>
    {{ $slot }}
</div>
