<!DOCTYPE html>
@php
    $local = session()->get('locale');
    $dir = $local == 'ar' ? 'rtl' : 'ltr';
    $ledgerCol = fn(string $k): bool => in_array($k, $ledger_visible_columns, true);
    $ledgerPhysicalPrefixKeys = ['ref_no', 'operation_date', 'narration', 'cost_center', 'added_by'];
    $ledgerFootLabelSpan = max(1, count(array_intersect($ledger_visible_columns, $ledgerPhysicalPrefixKeys)));
    $ledgerOpeningLabelSpan = max(1, count(array_intersect($ledger_visible_columns, $ledgerPhysicalPrefixKeys)));
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
            margin-bottom: 14px;
        }

        .ledger-header-top .h-company {
            font-size: 22px;
            font-weight: 900;
            color: #0a0a0a;
            margin: 0 0 12px 0;
            line-height: 1.25;
            letter-spacing: 0.02em;
        }

        html[dir="rtl"] .ledger-header-top .h-company {
            text-align: right;
        }

        html[dir="ltr"] .ledger-header-top .h-company {
            text-align: left;
        }

        .ledger-header-top .h-title {
            font-size: 18px;
            font-weight: 800;
            color: #111;
            margin: 0 0 4px 0;
            text-align: center;
        }

        .ledger-header-top .h-period {
            font-size: 12px;
            color: #555;
            margin: 0;
            text-align: center;
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
        @if (! empty($company?->name))
            <div class="h-company" style="text-align: {{ $dir === 'rtl' ? 'right' : 'left' }};">{{ $company->name }}</div>
        @endif
        <div class="h-title">
            @lang('accounting::lang.ledger')
            —
            {{ $account->gl_code }}
            {{ app()->getLocale() == 'ar' ? ' - ' : ' - ' }}
            {{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}
        </div>
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
                            <th>
                                @if ($ledgerCol('transaction'))
                                    @lang('accounting::lang.ledger_column_ref_with_type')
                                @else
                                    @lang('accounting::lang.transaction_number')
                                @endif
                            </th>
                        @endif
                        @if ($ledgerCol('operation_date'))
                            <th>@lang('accounting::lang.operation_date')</th>
                        @endif
                        @if ($ledgerCol('narration'))
                            <th>@lang('accounting::lang.ledger_narration')</th>
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
                                    @php
                                        $printRef = isset($transactions->accTransMapping)
                                            ? $transactions->accTransMapping->ref_no
                                            : (isset($transactions->transaction) ? $transactions->transaction->ref_no : null);
                                        $_pst = $transactions->sub_type ?? null;
                                        $printTypeLabel = $_pst
                                            ? (\Illuminate\Support\Facades\Lang::has('accounting::lang.'.$_pst)
                                                ? __('accounting::lang.'.$_pst)
                                                : $_pst)
                                            : '—';
                                    @endphp
                                    <span style="font-weight: 700;">{{ $printRef ?? '--' }}</span>
                                    @if ($ledgerCol('transaction'))
                                        <span style="display:inline-block;margin-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}:6px;font-size:10px;color:#555;border:1px solid #ccc;border-radius:4px;padding:2px 6px;">{{ $printTypeLabel }}</span>
                                    @endif
                                </td>
                            @endif
                            @if ($ledgerCol('operation_date'))
                                <td>{{ \Carbon\Carbon::parse($transactions->operation_date)->format('d/m/Y') }}</td>
                            @endif
                            @if ($ledgerCol('narration'))
                                <td>{{ $narr }}</td>
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
</body>

</html>
