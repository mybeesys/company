<!DOCTYPE html>
@php
    $local = app()->currentLocale();
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';
@endphp
<html lang="{{ $local }}" direction="{{ $dir }}" dir="{{ $dir }}"
    style="direction: {{ $dir }}">

<head>
    @include('layouts.css-references')
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            font-optical-sizing: 'auto';
            font-style: normal;
        }

        .bg-yellow {
            background-color: #ebb81e !important;
        }

        @page {
            margin-top: 20px;
        }
    </style>
</head>

<body class="bg-white">
    {{-- <h1 class="mx-auto mb-5">@lang('employee::general.employee_details'): {{ $employee->{get_name_by_lang()} }}</h1> --}}
    <div class="table-responsive mx-auto">
        <table class="table table-bordered min-w-700px">
            <thead>
                <th colspan="2" class="text-center fs-3 bg-yellow text-white">@lang('employee::fields.employee'):
                    {{ $employee->{get_name_by_lang()} }}</th>
            </thead>
            <tbody class="fs-4 border-2">
                <tr>
                    <td colspan="2" class="text-center">@lang('employee::general.employee_details')</td>
                </tr>
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.name')</td>
                    <td class="py-2 border-2">{{ $employee->name }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.name_en')</td>
                    <td class="py-2 border-2">{{ $employee->name_en }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.email')</td>
                    <td class="py-2 border-2">{{ $employee->email }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.phone_number')</td>
                    <td class="py-2 border-2">{{ $employee->phone_number }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.employment_start_date')</td>
                    <td class="py-2 border-2">{{ $employee->employment_start_date }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.employment_end_date')</td>
                    <td class="py-2 border-2">{{ $employee->employment_end_date }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.default_establishment')</td>
                    <td class="py-2 border-2">{{ $employee->defaultEstablishment->{get_name_by_lang()} }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="text-center">@lang('employee::general.pos')</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.employee_access_pin')</td>
                    <td class="py-2 border-2">{{ $employee->pin }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.status')</td>
                    <td class="py-2 border-2">
                        {{ $employee->pos_is_active ? __('employee::fields.active') : __('employee::fields.inactive') }}
                    </td>
                </tr>
                @foreach ($employee->posRoles->unique() as $role)
                    <tr>
                        <td class="py-2 border-2 bg-light">@lang('employee::fields.pos_role'): {{ $role->name }}</td>
                        <td class="py-2 border-2">
                            {{ implode(',', $role->establishments->pluck(get_name_by_lang())->toArray()) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="2" class="text-center">@lang('employee::general.dashboard_access')</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.user_name')</td>
                    <td class="py-2 border-2">{{ $employee->user_name }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.dashboard_roles')</td>
                    <td class="py-2 border-2">
                        {{ implode(',', $employee->dashboardRoles->pluck('name')->toArray()) }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="text-center">@lang('employee::fields.wage')</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.wage')</td>
                    <td class="py-2 border-2">{{ $employee->wage?->rate }}</td>
                </tr>
                <tr>
                    <td class="py-2 border-2 bg-light">@lang('employee::fields.wage_type')</td>
                    <td class="py-2 border-2">
                        @if ($employee->wage?->wage_type)
                            @lang('employee::fields.' . $employee->wage?->wage_type)
                        @endif
                    </td>
                </tr>
                @foreach ($employee->allowances->where('apply_once', false) as $allowance)
                    <tr>
                        <td class="py-2 border-2 bg-light">@lang('employee::fields.allowance'):
                            {{ $allowance->adjustmentType->{get_name_by_lang()} }}</td>
                        <td class="py-2 border-2">{{ $allowance->amount }}</td>
                    </tr>
                @endforeach
                @foreach ($employee->deductions->where('apply_once', false) as $deductions)
                    <tr>
                        <td class="py-2 border-2 bg-light">@lang('employee::fields.allowance'):
                            {{ $deductions->adjustmentType->{get_name_by_lang()} }}</td>
                        <td class="py-2 border-2">{{ $deductions->amount }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('layouts.js-references')
</body>

</html>
