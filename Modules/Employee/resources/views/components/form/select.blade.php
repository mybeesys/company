@props(['class' => '', 'options', 'name', 'errors', 'value' => null])

<select {{ $attributes->merge(['class' => 'form-select form-select-solid fw-bold ' . $class]) }} data-kt-select2="true"
    data-placeholder="@lang('employee::general.select_option')" data-allow-clear="true" data-kt-filter="{{ $name }}"
    name= "{{ $name }}" data-hide-search="true">
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
