<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
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
            font-size: 14px;
            padding: 10px;
            margin: 10px;
            color: #333;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
        }

        .table_component table {
            border: 1px solid #dededf;
            table-layout: auto;
            border-collapse: collapse;
            width: 100%;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
        }

        .table_component th {
            border: 1px solid #dededf;
            background-color: #eceff1;
            color: #000000;
            padding: 7px;
            text-align: center;
            font-size: 12px;
        }

        .table_component td {
            border: 1px solid #dededf;
            background-color: #ffffff;
            color: #000000;
            padding: 7px;
            font-size: 12px;
        }

        .report-meta {
            margin-bottom: 16px;
            line-height: 1.6;
        }

        tfoot td {
            font-weight: bold;
            background-color: #f5f5f5 !important;
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
            var params = new URLSearchParams(@json($exportQuery ?? []));
            var url = '{{ url('cost-center-transactions') }}/' + {{ $costCenter->id }};
            var qs = params.toString();
            window.location.href = qs ? url + '?' + qs : url;
        };
    </script>
</head>

<body>
    <div class="report-meta">
        <p><strong>@lang('accounting::lang.cost_center_transactions')</strong></p>
        <p>
            @lang('accounting::lang.cost_center'):
            {{ app()->getLocale() == 'ar' ? $costCenter->name_ar : $costCenter->name_en }}
            ({{ $costCenter->account_center_number }})
        </p>
        <p>
            {{ __('accounting::lang.ledger_report_period', [
                'from' => \Carbon\Carbon::parse($start_date)->format('d/m/Y'),
                'to' => \Carbon\Carbon::parse($end_date)->format('d/m/Y'),
            ]) }}
        </p>
        <p>{{ $transactions->count() }} @lang('messages.transactions')</p>
    </div>

    <div class="content table_component">
        <table class="table table-bordered table-striped" dir="{{ $dir }}">
            <thead>
                <tr>
                    <th>@lang('accounting::lang.transaction_number')</th>
                    <th>@lang('accounting::lang.operation_date')</th>
                    <th>@lang('accounting::lang.account_name')</th>
                    <th>@lang('accounting::lang.ledger_narration')</th>
                    <th>@lang('accounting::lang.added_by')</th>
                    <th>@lang('accounting::lang.debit')</th>
                    <th>@lang('accounting::lang.credit')</th>
                </tr>
            </thead>
            <tbody>
                @include('accounting::costCenter.partials.transaction_rows', ['transactions' => $transactions])
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: {{ $dir == 'rtl' ? 'left' : 'right' }};">
                        @lang('accounting::lang.total')
                    </td>
                    <td>{{ number_format($totalDebit, 2) }}</td>
                    <td>{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>

</html>
