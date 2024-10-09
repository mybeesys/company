@props([
    'class' => '',
    'options',
    'name',
    'errors',
    'value' => null,
    'label' => null,
    'required' => false,
    'labelClass' => '',
])
@if ($label)
    <label @class(['form-label', 'required' => $required, $labelClass])>{{ $label }}</label>
@endif

<select @class(['form-select', $class, 'is-invalid' => $errors->first($name)]) data-placeholder="@lang('employee::general.select_option')" data-kt-repeater="{{ $name }}"
    data-allow-clear="true" data-kt-filter="{{ $name }}" name="{{ $name }}">
    <option></option>
    @if ($options)
        @foreach ($options as $option)
            <option value="{{ $option['id'] }}" @selected($option['id'] == $value)>{{ $option['name'] }}</option>
        @endforeach
    @endif
</select>
@if ($errors->first($name))
    <div class="invalid-feedback">{{ $errors->first($name) }}</div>
@endif
