@props(['class' => '', 'row' => true])
<div @class(['fv-row', 'flex-md-root' => $row, $class])>
    {{ $slot }}
</div>
