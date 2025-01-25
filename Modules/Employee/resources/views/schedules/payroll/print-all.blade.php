<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';

@endphp
<html dir="{{ $dir }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print: @lang('menuItemLang.payroll')</title>
    @include('layouts.css-references')
    <style>
        * {
            font-family: DejaVu Sans !important;
        }

        body {
            font-size: 16px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 5px;
            /* margin: 10px; */
            background-color: white;
            color: #777;
        }


        body {
            color: #777;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
        }



        .table_component {
            overflow: auto;
        }

        .table_component table {
            border: 1px solid #dededf;
            table-layout: auto;
            border-collapse: collapse;
            border-spacing: 1px;
            page-break-before: avoid;
            page-break-after: avoid;
            width: 100%;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
        }

        .table_component caption {
            caption-side: top;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
        }

        .table_component th {
            border: 1px solid #dededf;
            background-color: #eceff1;
            color: #000000;
            padding: 7px;
            text-align: center;
        }

        .table_component td {
            border: 1px solid #dededf;
            background-color: #ffffff;
            color: #000000;
            padding: 7px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>

    <script>
        window.onload = function() {
            window.print();
        };
        // Return back after print
        window.onafterprint = function() {
            window.location.href = "{{ route('schedules.payrolls.index') }}";
        };
    </script>
</head>


<body>

    <div class="section">
        <div class="content table_component overflow-hidden">
            <table class="table table-bordered table-striped hide-footer" dir="{{ $dir }}" id="journal_table">
                <thead>
                    <tr>
                        <th class="align-middle">@lang('employee::fields.employee')</th>
                        <th class="align-middle">@lang('employee::fields.payroll_group_name')</th>
                        <th class="align-middle">@lang('employee::fields.date')</th>
                        <th class="align-middle">@lang('employee::fields.regular_worked_hours')</th>
                        <th class="align-middle">@lang('employee::fields.overtime_hours')</th>
                        <th class="align-middle">@lang('employee::fields.total_hours')</th>
                        <th class="align-middle">@lang('employee::fields.total_worked_days')</th>
                        <th class="align-middle">@lang('employee::fields.basic_wage')</th>
                        <th class="align-middle">@lang('employee::fields.total_allowances')</th>
                        <th class="align-middle">@lang('employee::fields.total_deductions')</th>
                        <th class="align-middle">@lang('employee::fields.wage_due')</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($payrolls as $payroll)
                        <tr>
                            <td class="min-w-125px">{{ $payroll->employee?->{get_name_by_lang()} }}</td>
                            <td>{{ $payroll->payrollGroup?->name }}</td>
                            <td class="text-nowrap">{{ $payroll->payrollGroup?->date }}</td>
                            <td>{{ $payroll->regular_worked_hours }}</td>
                            <td>{{ $payroll->overtime_hours }}</td>
                            <td>{{ $payroll->total_hours }}</td>
                            <td>{{ $payroll->total_worked_days }}</td>
                            <td>{{ $payroll->basic_total_wage }}</td>
                            <td>{{ $payroll->allowances }}</td>
                            <td>{{ $payroll->deductions }}</td>
                            <td>{{ $payroll->total_wage }}</td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

        </div>
    </div>
    @include('layouts.js-references')
</body>

</html>
