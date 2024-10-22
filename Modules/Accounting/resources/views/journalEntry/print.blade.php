<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $rtl_files = $local == 'ar' ? '.rtl' : '';


@endphp
<html dir="{{$dir}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print: {{ $journal->ref_no }}</title>

    {{-- <link href="{{ asset('assets/css/style.bundle' . $rtl_files . '.css') }}" rel="stylesheet" type="text/css" /> --}}

    {{-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&display=swap"
        rel="stylesheet"> --}}
    <!-- jsPDF (for PDF export) -->
    {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- SheetJS (for Excel export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script> --}}

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
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};  /* border: 1px solid; */
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
        }

        .table_component caption {
            caption-side: top;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};    }

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
            window.location.href = "{{ route('journal-entry-index') }}";
        };
    </script>
</head>

{{-- <body dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" style="text-align: {{ app()->getLocale() == 'ar' ? 'right' : 'left' }};"> --}}

<body >

    <div class="template-header">
        {{-- <h1>Print Page</h1> --}}

    </div>

    <div class="section">
        <div class="section-header">
            <p>@lang('accounting::fields.ref_no'): {{ $journal->ref_no }}</p>
            <p>@lang('accounting::lang.operation_date'): {{ \Carbon\Carbon::parse($journal->operation_date)->format('Y-m-d') }}</p>
            <p>@lang('accounting::lang.additionalNotes'): {{ $journal->note }}</p>

        </div>

        <div class="content table_component">
            <table class="table table-bordered table-striped hide-footer" id="journal_table">
                <thead>
                    <tr>
                        <th>@lang('accounting::lang.account')</th>
                        <th>@lang('accounting::lang.cost_center')</th>
                        <th>@lang('accounting::lang.debit')</th>
                        <th>@lang('accounting::lang.credit')</th>
                        <th>@lang('accounting::lang.added_by')</th>
                        <th>@lang('accounting::lang.additionalNotes')</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $total_debit = 0;
                        $total_credit = 0;
                    @endphp
                    @foreach ($journal->transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->account->gl_code }} -
                                {{ app()->getLocale() == 'ar' ? $transaction->account->name_ar : $transaction->account->name_en }}
                            </td>
                            <td>{{ $transaction->cost_center ? (app()->getLocale() == 'ar' ? $transaction->cost_center->name_ar : $transaction->cost_center->name_en) : '--' }}
                            </td>
                            <td>
                                @if ($transaction->type == 'debit')
                                    {{ $transaction->amount }}
                                    @php
                                        $total_debit += $transaction->amount;
                                    @endphp
                                @else
                                    0.0
                                @endif
                            </td>
                            <td>
                                @if ($transaction->type == 'credit')
                                    {{ $transaction->amount }}
                                    @php
                                        $total_credit += $transaction->amount;
                                    @endphp
                                @else
                                    0.0
                                @endif
                            </td>
                            <td>{{ $transaction->createdBy->name }}</td>
                            <td>{{ $transaction->note }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-center"><strong>@lang('messages.total')</strong></td>
                        <td><strong>{{ $total_debit }}</strong></td>
                        <td><strong>{{ $total_credit }}</strong></td>
                        @php
                            $text = '';
                            $Budget = $total_debit - $total_credit;
                            $budgetDifferenceText = __(
                                'accounting::lang.The journal entry is unbalanced with a difference of',
                            );
                            $text = $budgetDifferenceText . ' : ( ' . abs($Budget) . ' ) ';
                        @endphp
                        <td colspan="2" id="Budget" style="text-align: center;color:red">
                            {{ $Budget > 0 ?? $text }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <hr style="width:100%;text-align:left;margin-left:0">


        </div>
    </div>
</body>

</html>
