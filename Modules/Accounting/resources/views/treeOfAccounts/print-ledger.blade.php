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
            font-size: 13px;
            font-family: 'DejaVu Sans', 'Roboto', 'Montserrat', 'Open Sans', sans-serif;
            padding: 18px 18px 16px 18px;
            margin: 0;
            color: #333;
            text-align: {{ session()->get('locale') == 'ar' ? 'right' : 'left' }};
        }

        .ledger-header-top {
            text-align: center;
            margin-bottom: 14px;
        }

        .ledger-header-top .h-title {
            font-size: 18px;
            font-weight: 800;
            color: #111;
            margin: 0 0 4px 0;
        }

        .ledger-header-top .h-company {
            font-size: 12px;
            color: #444;
            margin: 0 0 3px 0;
        }

        .ledger-header-top .h-period {
            font-size: 12px;
            color: #555;
            margin: 0;
        }

        .ledger-header-grid {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }

        .ledger-header-grid .col {
            display: table-cell;
            vertical-align: top;
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

        .ledger-meta-box {
            border: 1px solid #333;
            border-radius: 8px;
            padding: 10px 10px;
        }

        .ledger-meta-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }

        .ledger-meta-row .k,
        .ledger-meta-row .v {
            display: table-cell;
            vertical-align: top;
        }

        .ledger-meta-row .k {
            width: 26%;
            color: #111;
            font-weight: bold;
        }

        .ledger-meta-row .v {
            width: 74%;
        }

        .table_component table {
            border: 1px solid #333;
            table-layout: auto;
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
        }

        .table_component {
            margin-top: 10px;
        }

        .table_component th {
            border: 1px solid #333;
            background-color: #e8e8e8;
            color: #000;
            padding: 10px 7px;
            text-align: center;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0;
        }

        .table_component td {
            border: 1px solid #666;
            background-color: #ffffff;
            color: #000;
            padding: 8px 6px;
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

        .ledger-sign {
            margin-top: 14px;
            display: table;
            width: 100%;
        }

        .ledger-sign .box {
            display: table-cell;
            border: 1px solid #333;
            padding: 8px;
            width: 33.33%;
            height: 64px;
            vertical-align: top;
        }

        .ledger-sign .lbl {
            font-size: 11px;
            color: #111;
            font-weight: bold;
            margin-bottom: 6px;
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

    <div class="ledger-header-top">
        <div class="h-title">
            @lang('accounting::lang.ledger')
            —
            {{ $account->gl_code }}
            {{ app()->getLocale() == 'ar' ? ' - ' : ' - ' }}
            {{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}
        </div>
        @if (! empty($company?->name))
            <div class="h-company">{{ $company->name }}</div>
        @endif
        @if ($periodLine)
            <div class="h-period">{{ $periodLine }}</div>
        @endif
    </div>

    {{-- Account details box intentionally removed (Saudi-style clean header) --}}

        <div class="content table_component">
            <table id="journal_table">
                <thead>
                    <tr>
                        <th style="width: 34px;">م</th>
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
                        $rowNo = 0;
                    @endphp
                    <tr class="ledger-opening-row">
                        <td class="num">—</td>
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
                            $rowNo++;
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
                            <td class="num">{{ $rowNo }}</td>
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
                    @php
                        $netMovementSigned = $is_debit_nature
                            ? ((float) $total_debit - (float) $total_credit)
                            : ((float) $total_credit - (float) $total_debit);
                        $netMovementIsCredit = $netMovementSigned < 0;
                        $netMovementAbs = abs($netMovementSigned);
                        $totalsLabel = app()->getLocale() == 'ar' ? 'المجموع' : 'Totals';
                        $netLabel = app()->getLocale() == 'ar' ? 'صافي الحركة' : 'Net movement';
                        $endingLabel = app()->getLocale() == 'ar' ? 'الرصيد الختامي' : __('accounting::lang.Closing balance');
                        $totalsHint = app()->getLocale() == 'ar'
                            ? 'المجموع = مجموع مبالغ المدين ومجموع مبالغ الدائن خلال الفترة (لا يشمل الرصيد الافتتاحي).'
                            : 'Totals = sum of debit amounts and sum of credit amounts during the period (excludes opening balance).';
                        $netHint = app()->getLocale() == 'ar'
                            ? 'صافي الحركة = (المدين − الدائن) للحسابات ذات الطبيعة المدينة، و(الدائن − المدين) للحسابات ذات الطبيعة الدائنة. لا يشمل الرصيد الافتتاحي.'
                            : 'Net movement = (debit − credit) for debit-nature accounts, and (credit − debit) for credit-nature accounts. Excludes opening balance.';
                        $endingHint = app()->getLocale() == 'ar'
                            ? 'الرصيد الختامي = الرصيد الافتتاحي + صافي الحركة (بحسب طبيعة الحساب).'
                            : 'Ending balance = opening balance + net movement (respecting the account nature).';
                    @endphp

                    <tr class="ledger-foot-row">
                        <td class="num">—</td>
                        <td colspan="{{ $ledgerFootLabelSpan }}" style="text-align: center;" title="{{ $totalsHint }}">
                            {{ $totalsLabel }}
                        </td>
                        @if ($ledgerCol('debit'))
                            <td class="num">@format_currency($total_debit)</td>
                        @endif
                        @if ($ledgerCol('credit'))
                            <td class="num">@format_currency($total_credit)</td>
                        @endif
                        @if ($ledgerCol('balance'))
                            <td class="num">—</td>
                        @endif
                    </tr>

                    <tr class="ledger-foot-row">
                        <td class="num">—</td>
                        <td colspan="{{ $ledgerFootLabelSpan }}" style="text-align: center;" title="{{ $netHint }}">
                            {{ $netLabel }}
                        </td>
                        @if ($ledgerCol('debit'))
                            <td class="num">
                                @if (! $netMovementIsCredit)
                                    @format_currency($netMovementAbs)
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        @if ($ledgerCol('credit'))
                            <td class="num">
                                @if ($netMovementIsCredit)
                                    @format_currency($netMovementAbs)
                                @else
                                    —
                                @endif
                            </td>
                        @endif
                        @if ($ledgerCol('balance'))
                            <td class="num">—</td>
                        @endif
                    </tr>

                    <tr class="ledger-foot-row">
                        <td class="num">—</td>
                        <td colspan="{{ $ledgerFootLabelSpan }}" style="text-align: center;" title="{{ $endingHint }}">
                            {{ $endingLabel }}
                        </td>
                        @if ($ledgerCol('debit'))
                            <td class="num">—</td>
                        @endif
                        @if ($ledgerCol('credit'))
                            <td class="num">—</td>
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

        <div class="ledger-sign">
            <div class="box">
                <div class="lbl">{{ app()->getLocale() == 'ar' ? 'إعداد' : 'Prepared by' }}</div>
            </div>
            <div class="box">
                <div class="lbl">{{ app()->getLocale() == 'ar' ? 'مراجعة' : 'Reviewed by' }}</div>
            </div>
            <div class="box">
                <div class="lbl">{{ app()->getLocale() == 'ar' ? 'اعتماد' : 'Approved by' }}</div>
            </div>
        </div>
    </div>
</body>

</html>
