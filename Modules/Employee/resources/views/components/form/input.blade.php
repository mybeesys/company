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
])
@if ($label)
    <label @class(['form-label', 'required' => $required, $labelClass])>{{ $label }}</label>
@endif
@includeWhen($hint, 'employee::components.forms.field-hint', ['hint' => $hint])
{{ $slot }}
<input type="{{ $type }}" name="{{ $name }}" placeholder="{{ $placeholder }}" id="{{ $name }}"
    value="{{ old($name, $value) }}" @class(['form-control', 'is-invalid' => $errors->first($name), $class]) @required($required)
    @checked($checked)
    />
@if ($errors->first($name))
    <div class="invalid-feedback">{{ $errors->first($name) }}</div>
@endif
