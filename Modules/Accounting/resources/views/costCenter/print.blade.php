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
    <title>Print: @lang('menuItemLang.costCenter')</title>
    <style>
        * {
            font-family: DejaVu Sans !important;
        }

        body {
            font-size: 16px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 10px;
            margin: 10px;

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
            /* height: 99%; */
            table-layout: auto;
            border-collapse: collapse;
            border-spacing: 1px;
            /* text-align: right; */
            page-break-before: avoid;
            page-break-after: avoid;
            direction: ltr;
            width: 100%;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
            /* border: 1px solid; */
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

        td {
            padding: 10px;
            margin: 10px;
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

        window.onafterprint = function() {
            window.location.href = "{{ route('cost-center-index') }}";
        };
    </script>
</head>


<body>

    <div class="template-header">

    </div>

    <div class="section">
        <div class="section-header">
            <p>@lang('menuItemLang.costCenter')</p>
        </div>

        <div class="content table_component">
            <table class="table table-bordered table-striped hide-footer" id="journal_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.cost_center')</th>
                        <th>@lang('accounting::lang.add_cost_center_title')</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($CostCenter as $row)
                        <tr>
                            <td>{{ $row->account_center_number }} -
                                {{ app()->getLocale() == 'ar' ? $row->name_ar : $row->name_en }}
                            </td>
                            <td>{{ $row->parentCostCenter?->account_center_number }} -
                                {{ app()->getLocale() == 'ar' ? $row->parentCostCenter?->name_ar : $row->parentCostCenter?->name_en }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <hr style="width:100%;text-align:left;margin-left:0">


        </div>
    </div>
</body>

</html>
