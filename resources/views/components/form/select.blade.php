@props([
    'class' => '',
    'options',
    'name',
    'errors',
    'value' => null,
    'label' => null,
    'required' => false,
    'labelClass' => '',
    'placeholder' => __('employee::general.select_option'),
    'default_selection' => null,
    'default_selection_value' => null,
    'data_allow_clear' => true,
    'optionName' => 'name',
    'disabled' => false,
    'attribute' => null,
    'no_default' => false,
])
@php
    // handling repeaters errors
    // Convert array-style name (e.g., 'pos_role_repeater[0][role]') to dot notation (e.g., 'pos_role_repeater.0.role')
    $dotNotationName = str_replace(['[', ']'], ['.', ''], $name);
@endphp
@if ($label)
    <label @class(['form-label', 'required' => $required, $labelClass])>{{ $label }}</label>
@endif
{{ $slot }}
<select @class([
    'form-select d-flex',
    $class,
    'is-invalid' => $errors->first($dotNotationName),
]) data-placeholder="{{ $placeholder }}" data-kt-repeater="{{ $name }}"
    @required($required) @disabled($disabled) data-allow-clear="{{ $data_allow_clear }}"
    data-kt-filter="{{ $name }}" name="{{ $name }}" {{ $attribute }}>
    @if (!$no_default)
        <option value="{{ $default_selection_value }}">{{ $default_selection }}</option>
    @endif
    @if ($options)
        @foreach ($options as $option)
            <option value="{{ $option['id'] }}" @selected($option['id'] == old($name, $value))>{{ $option["{$optionName}"] }}</option>
        @endforeach
    @endif
</select>
@if ($errors->first($dotNotationName))
    <div class="invalid-feedback">{{ $errors->first($dotNotationName) }}</div>
@endif
