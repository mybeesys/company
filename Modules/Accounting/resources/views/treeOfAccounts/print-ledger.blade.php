<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $ledgerCol = fn(string $k): bool => in_array($k, $ledger_visible_columns, true);
    $ledgerPrefixKeys = ['ref_no', 'operation_date', 'narration', 'transaction', 'cost_center', 'added_by'];
    $ledgerFootLabelSpan = max(1, count(array_intersect($ledgerPrefixKeys, $ledger_visible_columns)));
    $ledgerOpeningLabelSpan = max(1, count(array_diff($ledger_visible_columns, ['balance'])));
    $opening_balance = $opening_balance ?? 0;
    $is_debit_nature = $is_debit_nature ?? true;
    $start_date = $start_date ?? null;
    $end_date = $end_date ?? null;
    $periodLine = ($start_date && $end_date)
        ? __('accounting::lang.ledger_report_period', [
            'from' => \Carbon\Carbon::parse($start_date)->format('d/m/Y'),
            'to' => \Carbon\Carbon::parse($end_date)->format('d/m/Y'),
        ])
        : '';
@endphp
<html dir="{{ $dir }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print: @lang('accounting::lang.ledger') - {{ $account->name_en }}</title>
    <style>
        * {
            font-family: DejaVu Sans !important;
        }

        body {
            font-size: 14px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 12px;
            margin: 0;
            color: #333;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
        }

        .ledger-print-title {
            font-size: 18px;
            font-weight: bold;
            color: #111;
            margin-bottom: 4px;
        }

        .ledger-print-meta {
            font-size: 12px;
            color: #555;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .table_component table {
            border: 1px solid #333;
            table-layout: auto;
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
        }

        .table_component th {
            border: 1px solid #333;
            background-color: #e8e8e8;
            color: #000;
            padding: 8px 6px;
            text-align: center;
            font-weight: bold;
        }

        .table_component td {
            border: 1px solid #666;
            background-color: #ffffff;
            color: #000;
            padding: 6px;
            vertical-align: top;
        }

        .num {
            text-align: {{ session()->get('locale') == 'ar' ? 'left' : 'right' }};
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .ledger-opening-row td {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .ledger-foot-row td {
            font-weight: bold;
            background-color: #ececec;
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
            window.location.href = "{{ url('ledger') }}" + "?account_id=" + {{ $account->id }};
        };
    </script>
</head>

<body>

    <div class="template-header">
        <div class="ledger-print-title text-center">@lang('accounting::lang.ledger')</div>
        <div class="ledger-print-meta text-center">
            <strong>{{ $account->gl_code }}</strong>
            —
            {{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}
            @if ($periodLine)
                <br>{{ $periodLine }}
            @endif
        </div>
    </div>

    <div class="section">
        <div class="ledger-print-meta" style="margin-bottom: 10px;">
            <strong>@lang('accounting::lang.ledger_report_account_class'):</strong>
            @lang('accounting::lang.' . $account->account_primary_type)
            @if ($account->account_sub_type)
                — {{ app()->getLocale() == 'ar' ? $account->account_sub_type->name_ar : $account->account_sub_type->name_en }}
            @endif
            <br>
            <strong>@lang('accounting::lang.balance'):</strong> @format_currency($current_bal)
        </div>

        <div class="content table_component">
            <table id="journal_table">
                <thead>
                    <tr>
                        @if ($ledgerCol('ref_no'))
                            <th>@lang('accounting::lang.transaction_number')</th>
                        @endif
                        @if ($ledgerCol('operation_date'))
                            <th>@lang('accounting::lang.operation_date')</th>
                        @endif
                        @if ($ledgerCol('narration'))
                            <th>@lang('accounting::lang.ledger_narration')</th>
                        @endif
                        @if ($ledgerCol('transaction'))
                            <th>@lang('accounting::lang.transaction')</th>
                        @endif
                        @if ($ledgerCol('cost_center'))
                            <th>@lang('accounting::lang.cost_center')</th>
                        @endif
                        @if ($ledgerCol('added_by'))
                            <th>@lang('accounting::lang.added_by')</th>
                        @endif
                        @if ($ledgerCol('debit'))
                            <th>@lang('accounting::lang.debit')</th>
                        @endif
                        @if ($ledgerCol('credit'))
                            <th>@lang('accounting::lang.credit')</th>
                        @endif
                        @if ($ledgerCol('balance'))
                            <th>@lang('accounting::lang.balance')</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @php
                        $balance = (float) $opening_balance;
                        $total_debit = 0;
                        $total_credit = 0;
                    @endphp
                    <tr class="ledger-opening-row">
                        <td colspan="{{ $ledgerOpeningLabelSpan }}" style="text-align: center;">
                            @lang('accounting::lang.opening_balance')
                        </td>
                        @if ($ledgerCol('balance'))
                            <td class="num">
                                {{ number_format(abs($balance), 2) }}
                                ({{ $balance < 0 ? __('accounting::lang.credit') : __('accounting::lang.debit') }})
                            </td>
                        @endif
                    </tr>
                    @foreach ($account_transactions as $transactions)
                        @php
                            if ($is_debit_nature) {
                                if ($transactions->type == 'debit') {
                                    $balance += $transactions->amount;
                                    $total_debit += $transactions->amount;
                                } else {
                                    $balance -= $transactions->amount;
                                    $total_credit += $transactions->amount;
                                }
                            } else {
                                if ($transactions->type == 'debit') {
                                    $balance -= $transactions->amount;
                                    $total_debit += $transactions->amount;
                                } else {
                                    $balance += $transactions->amount;
                                    $total_credit += $transactions->amount;
                                }
                            }
                            $narr = trim((string) ($transactions->note ?? ''));
                            if ($narr === '' && $transactions->accTransMapping && $transactions->accTransMapping->note) {
                                $narr = trim((string) $transactions->accTransMapping->note);
                            }
                            if ($narr === '') {
                                $narr = '—';
                            }
                        @endphp
                        <tr>
                            @if ($ledgerCol('ref_no'))
                                <td>
                                    @if (isset($transactions->accTransMapping))
                                        {{ $transactions->accTransMapping->ref_no }}
                                    @elseif (isset($transactions->transaction))
                                        {{ $transactions->transaction->ref_no }}
                                    @else
                                        --
                                    @endif
                                </td>
                            @endif
                            @if ($ledgerCol('operation_date'))
                                <td>{{ \Carbon\Carbon::parse($transactions->operation_date)->format('d/m/Y') }}</td>
                            @endif
                            @if ($ledgerCol('narration'))
                                <td>{{ $narr }}</td>
                            @endif
                            @if ($ledgerCol('transaction'))
                                <td>
                                    @if ($transactions->sub_type == 'sell')
                                        @lang('accounting::lang.sell')
                                    @elseif ($transactions->sub_type == 'sell_cash')
                                        @lang('accounting::lang.receipt_voucher')
                                    @elseif ($transactions->sub_type == 'sales_revenue')
                                        @lang('accounting::lang.payment_voucher')
                                    @else
                                        @lang('accounting::lang.' . $transactions->sub_type)
                                    @endif
                                </td>
                            @endif
                            @if ($ledgerCol('cost_center'))
                                <td>
                                    @if ($transactions->costCenter)
                                        {{ $transactions->costCenter->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $transactions->costCenter->name_ar : $transactions->costCenter->name_en) }}
                                    @else
                                        --
                                    @endif
                                </td>
                            @endif
                            @if ($ledgerCol('added_by'))
                                <td>{{ $transactions->createdBy->name ?? '—' }}</td>
                            @endif
                            @if ($ledgerCol('debit'))
                                <td class="num">
                                    @if ($transactions->type == 'debit')
                                        {{ number_format($transactions->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if ($ledgerCol('credit'))
                                <td class="num">
                                    @if ($transactions->type == 'credit')
                                        {{ number_format($transactions->amount, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endif
                            @if ($ledgerCol('balance'))
                                <td class="num">
                                    {{ number_format(abs($balance), 2) }}
                                    ({{ $balance < 0 ? __('accounting::lang.credit') : __('accounting::lang.debit') }})
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="ledger-foot-row">
                        <td colspan="{{ $ledgerFootLabelSpan }}" style="text-align: center;">
                            @lang('accounting::lang.Closing balance')
                        </td>
                        @if ($ledgerCol('debit'))
                            <td class="num">@format_currency($total_debit)</td>
                        @endif
                        @if ($ledgerCol('credit'))
                            <td class="num">@format_currency($total_credit)</td>
                        @endif
                        @if ($ledgerCol('balance'))
                            <td class="num">
                                @format_currency(abs($balance))
                                ({{ $balance < 0 ? __('accounting::lang.credit') : __('accounting::lang.debit') }})
                            </td>
                        @endif
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</body>

</html>
