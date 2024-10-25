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
    <title>@lang('accounting::lang.cost_center_transactions') - @lang('accounting::lang.cost_center') ({{ $costCenter->account_center_number }})
    </title>
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
            /* direction: ltr; */
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
            var url = '{{ url('cost-center-transactions') }}/' + {{ $costCenter->id }};

            window.location.href = url;
        };
    </script>
</head>


<body>

    <div class="template-header">

    </div>

    <div class="section">
        <div class="section-header">
            <p>@lang('accounting::lang.cost_center_transactions') - @lang('accounting::lang.cost_center')
                {{ app()->getLocale() == 'ar' ? $costCenter->name_ar : $costCenter->name_en }}
                ({{ $costCenter->account_center_number }})
            </p>
        </div>

        
        <div class="content table_component">
            <table class="table table-bordered table-striped hide-footer" dir="{{ $dir }}" id="journal_table">
                <thead>
                    <tr>
                        <th class="min-w-125px ">@lang('accounting::lang.transaction_number')</th>
                        <th class="min-w-80px">@lang('accounting::lang.operation_date')</th>
                        <th class="min-w-125px">@lang('accounting::lang.transaction')</th>
                        <th class="min-w-200px">@lang('accounting::lang.added_by')</th>
                        <th class="min-w-150px">@lang('accounting::lang.amount')</th>

                    </tr>
                </thead>

                <tbody>
                    @foreach ($costCenter->transactions as $transactions)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="d-flex justify-content-start flex-column">
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $transactions->accTransMapping->ref_no }}</a>

                                    </div>
                                </div>
                            </td>

                            <td>
                                <a
                                    class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">{{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A') }}</a>
                            </td>

                            <td>
                                <span class="badge badge-light-primary fs-7">@lang('accounting::lang.' . $transactions->sub_type)</span>
                            </td>


                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $transactions->createdBy->name }}</a>

                            </td>

                            <td>


                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $transactions->amount }}</a>



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
