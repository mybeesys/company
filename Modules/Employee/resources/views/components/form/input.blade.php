@props([
    'placeholder' => '',
    'name',
    'label' => null,
    'value' => null,
    'type' => 'text',
    'required' => false,
    'hint' => null,
    'errors',
])
@if ($label)
    <label @class(['form-label', 'required' => $required])>{{ $label }}</label>
@endif
@includeWhen($hint, 'employee::components.forms.field-hint', ['hint' => $hint])
{{ $slot }}
<input type="{{ $type }}" name="{{ $name }}" placeholder="{{ $placeholder }}" id="{{ $name }}"
    @class([
        'form-control',
        'is-invalid' => $errors->first($name),
    ]) value="{{ $value }}" {{--  @required($required) --}} />

@if ($errors->first($name))
    <div class="invalid-feedback">{{ $errors->first($name) }}</div>
@endif
