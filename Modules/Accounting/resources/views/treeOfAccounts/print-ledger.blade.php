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
    <title>Print: @lang('accounting::lang.ledger') - {{ $account->name_en }}</title>
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
            window.location.href = "{{ url('ledger') }}" + "?account_id=" + {{ $account->id }};

        };
    </script>
</head>


<body>

    <div class="template-header text-center">
        <h3>@lang('accounting::lang.ledger') - {{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }} -
            ({{ $account->gl_code }})</h3>
    </div>

    <div class="section">
        <div class="section-header">
            <p>@lang('accounting::lang.account_primary_type'): @lang('accounting::lang.' . $account->account_primary_type)</p>
            <p>@lang('accounting::lang.account_sub_type'):
                {{ app()->getLocale() == 'ar' ? $account->account_sub_type['name_ar'] : $account->account_sub_type['name_en'] }}
            </p>
            <p>@lang('accounting::lang.sub_account_type'):@if ($account->account_sub_type['account_primary_type'])
                    @lang('accounting::lang.' . $account->account_sub_type['account_primary_type'])
                @else
                    --
                @endif
            </p>
            <p>@lang('accounting::lang.account_category'): @if ($account->account_category)
                    @lang('accounting::lang.' . $account->account_category)
                @else
                    --
                @endif
            </p>
            <p>@lang('accounting::lang.account_type'): @if ($account->account_type)
                    @lang('accounting::lang.' . $account->account_type)
                @else
                    --
                @endif
            </p>
            <p>@lang('accounting::lang.balance'): <span class=" fw-semibold fs-2" style="color: #0945e9">
                    @format_currency($current_bal)</span></p>

        </div>

        <div class="content table_component">
            <table class="table table-bordered table-striped hide-footer" id="journal_table">
                <thead>
                    <tr>
                        <th class="min-w-125px ">@lang('accounting::lang.transaction_number')</th>
                                <th class="min-w-80px">@lang('accounting::lang.operation_date')</th>
                                <th class="min-w-125px">@lang('accounting::lang.transaction')</th>
                                <th class="min-w-125px">@lang('accounting::lang.cost_center')</th>
                                <th class="min-w-200px">@lang('accounting::lang.added_by')</th>
                                <th class="min-w-150px">@lang('accounting::lang.debit')</th>
                                <th class="min-w-150px">@lang('accounting::lang.credit')</th>
                                <th class="min-w-150px">@lang('accounting::lang.balance')</th>

                            </tr>
                </thead>

                <tbody>
                    @php
                        $balance = 0;
                        $total_debit = 0;
                        $total_credit = 0;
                    @endphp
                    @foreach ($account_transactions as $transactions)
                        @php
                            if ($transactions->type == 'debit') {
                                $balance += $transactions->amount;
                                $total_debit += $transactions->amount;
                            } elseif ($transactions->type == 'credit') {
                                $balance -= $transactions->amount;
                                $total_credit += $transactions->amount;
                            }
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="d-flex justify-content-start flex-column">
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            @if ($transactions->sub_type == 'journal_entry')
                                                {{ $transactions->accTransMapping->ref_no }}
                                            @else
                                                {{ $transactions->transaction->ref_no }}
                                            @endif
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">
                                    {{ \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $transactions->operation_date)->format('d/m/Y h:i A') }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-light-primary fs-7">@lang('accounting::lang.' . $transactions->sub_type)</span>
                            </td>
                            <td>
                                <span class="text-muted fw-semibold text-muted d-block fs-7">
                                    @if ($transactions->costCenter)
                                        {{ $transactions?->costCenter->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $transactions->costCenter->name_ar : $transactions->costCenter->name_en) }}
                                    @else
                                        --
                                    @endif
                                </span>
                            </td>
                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ $transactions->createdBy->name }}
                                </a>
                            </td>
                            <td>
                                @if ($transactions->type == 'debit')
                                    <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                        {{ number_format($transactions->amount, 2) }}
                                    </a>
                                @else
                                    <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                        --
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($transactions->type == 'credit')
                                    <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                        {{ number_format($transactions->amount, 2) }}
                                    </a>
                                @else
                                    <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                        --
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                    {{ number_format($balance, 2) }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class=" text-center fw-bold fs-4">صافي الحركة</td>
                        <td colspan="1" class=" fw-bold fs-5">
                         @format_currency($total_debit)
                        </td>
                        <td  class=" fw-bold fs-5">
                            @format_currency( $total_credit)
                        </td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-center fw-bold fs-4">الرصيد الختامي</td>
                        <td colspan="2" class=" fw-bold fs-5">
                            @format_currency($balance)
                        </td>
                    </tr>
                </tfoot>

            </table>

            <hr style="width:100%;text-align:left;margin-left:0">


        </div>
    </div>
</body>

</html>
