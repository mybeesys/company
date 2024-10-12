@props([
    'placeholder' => '',
    'name',
    'label' => null,
    'value' => null,
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'errors',
    'class' => '',
    'labelClass' => '',
    'checked' => false,
    'datalist' => null,
])
@php
    // handling repeaters errors
    // Convert array-style name (e.g., 'role_wage_repeater[0][role]') to dot notation (e.g., 'role_wage_repeater.0.role')
    $dotNotationName = str_replace(['[', ']'], ['.', ''], $name);
@endphp
@if ($label)
    <label @class(['form-label', 'required' => $required, $labelClass])>{{ $label }}</label>
@endif
@includeWhen($hint, 'employee::components.forms.field-hint', ['hint' => $hint])
{{ $slot }}
<input type="{{ $type }}" list="{{ $name }}list" name="{{ $name }}"
    placeholder="{{ $placeholder }}" id="{{ $name }}" autocomplete="new-password" value="{{ old($name, $value) }}"
    @class([
        'form-control',
        'is-invalid' => $errors->first($dotNotationName),
        $class,
    ]) @required($required) @checked($checked) />
{{ $datalist }}
@if ($errors->first($dotNotationName))
    <div class="invalid-feedback">{{ $errors->first($dotNotationName) }}</div>
@endif
