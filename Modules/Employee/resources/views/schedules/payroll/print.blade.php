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
    <div class="table-responsive mx-auto">
        <table class="table table-bordered min-w-700px">
            <thead>
                <th colspan="2" class="text-center fs-3 bg-yellow text-white">@lang('employee::general.payroll_for_employee'):
                    {{ $payroll->employee->{get_name_by_lang()} }} ({{ $payroll->payrollGroup->date }})</th>
            </thead>
            <tbody class="fs-4 border-2">
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.regular_worked_hours')</td>
                    <td class="py-2 border-2">{{ convertToHoursMinutesHelper($payroll->regular_worked_hours * 60) }}
                    </td>
                </tr>
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.overtime_hours')</td>
                    <td class="py-2 border-2">{{ convertToHoursMinutesHelper($payroll->overtime_hours * 60) }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.total_hours')</td>
                    <td class="py-2 border-2">{{ convertToHoursMinutesHelper($payroll->total_hours * 60) }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.total_worked_days')</td>
                    <td class="py-2 border-2">{{ $payroll->total_worked_days }}</td>
                </tr>
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.basic_total_wage')</td>
                    <td class="py-2 border-2">{{ $payroll->basic_total_wage }}</td>
                </tr>
                @if ($payroll->allowances()->exists())
                    @php
                        $uniqueAllowances = $payroll
                            ->allowances()
                            ->get()
                            ->groupBy('adjustment_type_id')
                            ->map(function ($group) {
                                return [
                                    'name' =>
                                        $group->first()->adjustmentType->{get_name_by_lang()} ??
                                        ($group->first()->name ?? $group->first()->name_en),
                                    'total_amount' => $group->sum('amount'),
                                ];
                            });
                    @endphp
                    <tr>
                        <td colspan="2" class="text-center">@lang('employee::fields.allowances')</td>
                    </tr>
                    @foreach ($uniqueAllowances as $allowance)
                        <tr>
                            <td class="py-2 border-2 bg-light">
                                {{ $allowance['name'] }}</td>
                            <td class="py-2 border-2">{{ $allowance['total_amount'] }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.total_allowances')</td>
                        <td class="py-2 border-2">{{ $payroll->allowances }}</td>
                    </tr>
                @endif
                @if ($payroll->deductions()->exists())
                    @php
                        $uniqueDeductions = $payroll
                            ->deductions()
                            ->get()
                            ->groupBy('adjustment_type_id')
                            ->map(function ($group) {
                                return [
                                    'name' =>
                                        $group->first()->adjustmentType->{get_name_by_lang()} ??
                                        ($group->first()->name ?? $group->first()->name_en),
                                    'total_amount' => $group->sum('amount'),
                                ];
                            });
                    @endphp
                    <tr>
                        <td colspan="2" class="text-center">@lang('employee::fields.deductions')</td>
                    </tr>
                    @foreach ($uniqueDeductions as $deduction)
                        <tr>
                            <td class="py-2 border-2 bg-light">
                                {{ $deduction['name'] }}</td>
                            <td class="py-2 border-2">{{ $deduction['total_amount'] }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.total_deductions')</td>
                        <td class="py-2 border-2">{{ $payroll->deductions }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="2" class="text-center"></td>
                </tr>
                <tr>
                    <td class="py-2 text-dark bg-light border-2">@lang('employee::fields.total_wage_due')</td>
                    <td class="py-2 border-2">{{ $payroll->total_wage }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @include('layouts.js-references')
</body>

</html>
