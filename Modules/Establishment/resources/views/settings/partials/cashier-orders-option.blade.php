@php
    $checked = (string) $current === (string) $value;
@endphp
<label class="cashier-order-option {{ $checked ? 'is-active' : '' }}">
    <input class="btn-check" type="radio" name="{{ $name }}" value="{{ $value }}" @checked($checked)>
    <span class="cashier-order-option-title">{{ $title }}</span>
    @if (! empty($hint))
        <span class="cashier-order-option-hint">{{ $hint }}</span>
    @endif
</label>
