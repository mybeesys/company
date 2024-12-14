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
    'attribute' => null,
    'form_control' => true,
    'disabled' => false,
    'readonly' => false,
])

@php
    // handling repeaters errors
    // Convert array-style name (e.g., 'pos_role_repeater[0][role]') to dot notation (e.g., 'pos_role_repeater.0.role')
    $dotNotationName = str_replace(['[', ']'], ['.', ''], $name);
@endphp
@if ($label)
    <label @class(['form-label', 'required' => $required, $labelClass])>{{ $label }}</label>
@endif
@includeWhen($hint, 'components.form.field-hint', ['hint' => $hint])
{{ $slot }}
<input type="{{ $type }}" list="{{ $name }}list" name="{{ $name }}" @readonly($readonly)
    placeholder="{{ $placeholder }}" id="{{ $name }}" autocomplete="new-password" value="{{ old($name, $value) }}"
    {{ $attribute }} @class([
        'form-control' => $form_control,
        'is-invalid' => $errors->first($dotNotationName),
        'form-control-solid',
        $class,
    ]) @required($required) @checked($checked) @disabled($disabled)/>
{{ $datalist }}
@if ($errors->first($dotNotationName))
    <div class="invalid-feedback">{{ $errors->first($dotNotationName) }}</div>
@endif
