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
    'optionName' => 'name'
])
@php
    // handling repeaters errors
    // Convert array-style name (e.g., 'role_wage_repeater[0][role]') to dot notation (e.g., 'role_wage_repeater.0.role')
    $dotNotationName = str_replace(['[', ']'], ['.', ''], $name);
@endphp
@if ($label)
    <label @class(['form-label', 'required' => $required, $labelClass])>{{ $label }}</label>
@endif

<select @class([
    'form-select',
    $class,
    'is-invalid' => $errors->first($dotNotationName),
]) data-placeholder="{{ $placeholder }}" data-kt-repeater="{{ $name }}"
    data-allow-clear="{{ $data_allow_clear }}" data-kt-filter="{{ $name }}" name="{{ $name }}">
    <option value="{{ $default_selection_value }}">{{ $default_selection }}</option>
    @if ($options)
        @foreach ($options as $option)
            <option value="{{ $option['id'] }}" @selected($option['id'] == $value)>{{ $option["{$optionName}"] }}</option>
        @endforeach
    @endif
</select>
@if ($errors->first($dotNotationName))
    <div class="invalid-feedback">{{ $errors->first($dotNotationName) }}</div>
@endif
