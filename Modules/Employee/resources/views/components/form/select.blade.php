@props(['class' => '', 'options', 'name', 'errors'])

<select {{ $attributes->merge(['class' => 'form-select form-select-solid fw-bold ' . $class]) }} data-kt-select2="true"
    data-placeholder="@lang('employee::general.select_option')" data-allow-clear="true" data-kt-filter="{{ $name }}"
    data-hide-search="true">

    <option></option>
    @foreach ($options as $option)
        <option value="{{ $option['value'] }}">{{ $option['name'] }}</option>
    @endforeach
</select>
@if ($errors->first($name))
    <div class="invalid-feedback">{{ $errors->first($name) }}</div>
@endif
