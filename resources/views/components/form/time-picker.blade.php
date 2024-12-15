@props(['label', 'name', 'hint' => null, 'errors', 'required' => false, 'value' => null, 'disabled' => false])
<label @class(['required' => $required, 'form-label'])>{{ $label }}</label>
@includeWhen($hint, 'components.form.field-hint', ['hint' => $hint])
<div class="input-group" id="{{ $name }}" data-td-target-input="nearest" data-td-target-toggle="nearest">
    <span class="input-group-text" data-td-target="#{{ $name }}" data-td-toggle="datetimepicker">
        <i class="ki-duotone ki-calendar fs-2"><span class="path1"></span><span class="path2"></span></i>
    </span>
    <input @class([
        'form-control',
        'form-control-solid',
        'is-invalid' => $errors->first($name),
    ]) value="{{ $value ? $value : old($name) }}" id="{{ $name }}_input"
        name="{{ $name }}" type="text" data-td-target="#{{ $name }}" />
    @if ($errors->first($name))
        <div class="invalid-feedback">{{ $errors->first($name) }}</div>
    @endif
</div>
