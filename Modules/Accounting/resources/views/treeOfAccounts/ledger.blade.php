@extends('layouts.app')

@section('title', __('accounting::lang.ledger'))
@section('css')
    <style>
        #kt_timeline_widget_3_tab_content_4>div:nth-child(3)>span {
            min-height: 40px !important;
        }

        .empty-content {
            display: grid;
            justify-content: center;
            text-align: center;
            gap: 11px;
            padding-bottom: 37px;
        }

        .ledger-stat-card {
            border: 1px solid #eef0f4;
            border-radius: 10px;
            padding: 12px 14px;
            background: #fcfcfd;
            min-height: 86px;
        }

        .ledger-col-toggle .form-check {
            margin-bottom: 0.35rem;
            white-space: nowrap;
        }

        .ledger-col-toggle .form-check-input {
            cursor: pointer;
        }

        .ledger-col-toggle .form-check-label {
            cursor: pointer;
            padding-inline-start: 0.35rem;
        }
    </style>
@stop
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1>

                        @lang('accounting::lang.ledger') - {{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }} /
                        {{ $account->gl_code }}

                    </h1>
                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
                <div class="w-100">
                    <select id="accounts" class="form-select form-select-solid select-2" name="id">
                        @foreach ($accountingAccount as $_account)
                            <option value="{{ $_account->id }}" @if ($account->id == $_account->id) selected @endif>
                                @if (app()->getLocale() == 'ar')
                                    {{ $_account->name_ar }}
                                @else
                                    {{ $_account->name_en }}
                                @endif -

                                {{ $_account->gl_code }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>



    <form action="{{ url('ledger') }}?account_id={{ $account->id }}" method="GET">
        <div class="row py-5">
            <div class="col-md-3">
                <label>{{ __('accounting::lang.from_date') }}</label>
                <input type="hidden" name="account_id" value="{{ $account->id }}"
                    class="form-control">
                <input type="date" name="start_date" value="{{ request('start_date', $start_date) }}"
                    class="form-control">
            </div>
            <div class="col-md-3">
                <label>{{ __('accounting::lang.to_date') }}</label>
                <input type="date" name="end_date" value="{{ request('end_date', $end_date) }}" class="form-control">
            </div>

            <div class="col-md-3">

                <div class="form-group">
                    <label for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}:</label>
                    <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                        class="form-select d-flex form-select-solid" multiple>
                        @foreach ($costCenters as $costCenter)
                            <option value="{{ $costCenter->id }}" @if (in_array($costCenter->id, $choose_cost_center_select ?? [])) selected @endif>
                                @if (app()->getLocale() == 'ar')
                                    {{ $costCenter->account_center_number . ' - ' . $costCenter->name_ar }}
                                @else
                                    {{ $costCenter->account_center_number . ' - ' . $costCenter->name_en }}
                                @endif

                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <label>{{ __('accounting::lang.transaction') }}</label>
                <select name="sub_type" class="form-select form-select-solid select-2">
                    <option value="">{{ __('messages.view_all') }}</option>
                    @foreach ($subTypes as $subType)
                        <option value="{{ $subType }}" @selected(request('sub_type') === $subType)>
                            {{ __('accounting::lang.' . $subType) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mt-4">
                <label>{{ __('accounting::lang.transaction_number') }}</label>
                <input type="text" name="ref_no" class="form-control" value="{{ request('ref_no') }}"
                    placeholder="{{ __('accounting::lang.transaction_number') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end mt-4">
                <button type="submit" class="btn btn-primary">{{ __('report::general.filter') }}</button>
                <a href="{{ route('ledger', ['account_id' => $account->id]) }}" class="btn btn-light ms-2">@lang('sales::lang.Remove filter')</a>
            </div>
        </div>
    </form>
    {{-- <div class="tab-content mb-2 px-9 " @if (app()->getLocale() == 'ar') dir="rtl" @endif>


        <div class="tab-pane fade row show active" id="kt_timeline_widget_3_tab_content_4" role="tabpanel">

            <div class="d-flex col-10 align-items-center mb-3">
                <span data-kt-element="bullet"
                    class="bullet bullet-vertical d-flex align-items-center min-h-70px mh-100 me-4 bg-info"></span>
                <div class="flex-grow-1 me-5">
                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_name'): @if (app()->getLocale() == 'ar')
                            {{ $account->name_ar }}
                        @else
                            {{ $account->name_en }}
                        @endif -
                        <span class="text-gray-500 fw-semibold fs-5">
                            {{ $account->gl_code }} </span>
                    </div>
                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_primary_type'):
                        <span class="text-gray-500 fw-semibold fs-5">
                            @lang('accounting::lang.' . $account->account_primary_type) </span>
                    </div>

                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_sub_type'):
                        <span class="text-gray-500 fw-semibold fs-5">
                            @if (app()->getLocale() == 'ar')
                                {{ $account->account_sub_type['name_ar'] }}
                            @else
                                {{ $account->account_sub_type['name_en'] }}
                            @endif
                        </span>
                    </div>

                </div>

                <div class="col">
                    <div class="row-cols">
                        <div class="text-gray-800 fw-semibold fs-5">
                            @lang('accounting::lang.sub_account_type'):
                            <span class="text-gray-500 fw-semibold fs-5">
                                @if ($account->account_sub_type['account_primary_type'])
                                    @lang('accounting::lang.' . $account->account_sub_type['account_primary_type'])
                            </span>
                        @else
                            --
                            @endif
                        </div>
                    </div>


                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.account_type'):
                        <span class="text-gray-500 fw-semibold fs-4">
                            @if ($account->account_type)
                                @lang('accounting::lang.account_types.' . $account->account_type)
                            @else
                                --
                            @endif
                        </span>
                    </div>

                </div>


            </div>


            <div class="d-flex align-items-center mb-6">

                <span data-kt-element="bullet"
                    class="bullet bullet-vertical d-flex align-items-center min-h-58px mh-100 me-4 bg-success"
                    style="height: 27px;"></span>



                <div class="flex-grow-1 me-5">


                    <div class="text-gray-800 fw-semibold fs-5">
                        @lang('accounting::lang.balance'):
                        <span class=" fw-semibold fs-2" style="color: #0945e9">
                            @format_currency($current_bal)</span>
                    </div>



                </div>


            </div>

        </div>
    </div> --}}


    <div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>

        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.account_transactions')</span>

                <span class="text-muted mt-1 fw-semibold fs-7">
                    @if (count($account_transactions) > 0)
                        {{ count($account_transactions) }} @lang('messages.transactions')
                    @endif

                </span>
            </h3>
            @if (count($account_transactions) > 0)
                <div class="card-toolbar">
                    <div class="btn-group dropend">

                        <button type="button" style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                            class="btn  dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu"
                            style="width: max-content; padding: 10px;">
                            <li class="mb-5"
                                style="text-align: justify; border-bottom: 1px solid #00000029; padding:0.8rem;">
                                <span class="card-label fw-bold fs-6 mb-1 ">@lang('messages.settings')</span>
                            </li>
                            <li class="mb-3 pb-3 border-bottom ledger-col-toggle" onclick="event.stopPropagation();">
                                <span class="fw-semibold fs-7 d-block mb-2">@lang('accounting::lang.ledger_columns_visibility')</span>
                                @php
                                    $ledgerColMeta = [
                                        'ref_no' => __('accounting::lang.ledger_column_ref_no'),
                                        'operation_date' => __('accounting::lang.ledger_column_operation_date'),
                                        'transaction' => __('accounting::lang.ledger_column_transaction'),
                                        'cost_center' => __('accounting::lang.ledger_column_cost_center'),
                                        'added_by' => __('accounting::lang.ledger_column_added_by'),
                                        'debit' => __('accounting::lang.ledger_column_debit'),
                                        'credit' => __('accounting::lang.ledger_column_credit'),
                                        'balance' => __('accounting::lang.ledger_column_balance'),
                                    ];
                                @endphp
                                @foreach (\Modules\Accounting\Http\Controllers\TreeAccountsController::LEDGER_COLUMN_ORDER as $colKey)
                                    <div class="form-check">
                                        <input class="form-check-input ledger-col-cb" type="checkbox"
                                            id="ledger-col-{{ $colKey }}" data-ledger-col-key="{{ $colKey }}"
                                            @if ($colKey === 'balance') checked disabled title="{{ __('accounting::lang.ledger_column_balance_locked') }}"
                                            @elseif (in_array($colKey, $ledger_visible_columns, true)) checked @endif>
                                        <label class="form-check-label fs-8" for="ledger-col-{{ $colKey }}"
                                            @if ($colKey === 'balance') title="{{ __('accounting::lang.ledger_column_balance_locked') }}" @endif>
                                            {{ $ledgerColMeta[$colKey] }}</label>
                                    </div>
                                @endforeach
                            </li>

                            <li>
                                <div class="menu-item-custom ">
                                    <a href="{{ url('/print-ledger', $account->id) }}?{{ http_build_query(['ledger_cols' => implode(',', $ledger_visible_columns)]) }}"
                                        class="btn ledger-export-link"
                                        data-ledger-export-base="{{ url('/print-ledger', $account->id) }}">@lang('accounting::fields.print')</a>
                                </div>
                            </li>

                            <li>
                                <div class="menu-item-custom ">
                                    <a href="{{ url('/ledger-export-pdf', $account->id) }}?{{ http_build_query(['ledger_cols' => implode(',', $ledger_visible_columns)]) }}"
                                        class="btn ledger-export-link"
                                        data-ledger-export-base="{{ url('/ledger-export-pdf', $account->id) }}">@lang('general.export_as_pdf')</a>
                                </div>
                            </li>

                            <li>
                                <div class="menu-item-custom ">
                                    <a href="{{ url('/ledger-export-excel', $account->id) }}?{{ http_build_query(['ledger_cols' => implode(',', $ledger_visible_columns)]) }}"
                                        class="btn ledger-export-link"
                                        data-ledger-export-base="{{ url('/ledger-export-excel', $account->id) }}">@lang('general.export_as_excel')</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif

        </div>



        @if (count($account_transactions) == 0)
            <div class="empty-content">
                <img src="/assets/media/illustrations/empty-content.svg" class=" w-200px" alt="">
                <span class="text-gray-500 fw-semibold fs-6" style="margin: 7px -34px;">
                    @lang('messages.no_account_transactions')</span>
            </div>
        @else
            <div class="card-body py-3">
                @php
                    $periodDebit = (float) $account_transactions->where('type', 'debit')->sum('amount');
                    $periodCredit = (float) $account_transactions->where('type', 'credit')->sum('amount');
                    $closingBalance = (float) $current_bal;
                    $openingNatureLabel = $opening_balance >= 0 ? __('accounting::lang.debit') : __('accounting::lang.credit');
                    $closingNatureLabel = $closingBalance >= 0 ? __('accounting::lang.debit') : __('accounting::lang.credit');
                @endphp
                <div class="row g-3 mb-5">
                    <div class="col-md-3">
                        <div class="ledger-stat-card">
                            <div class="text-muted fs-7">@lang('accounting::lang.opening_balance')</div>
                            <div class="fw-bold fs-4">{{ number_format(abs($opening_balance), 2) }} <span class="fs-7 text-muted">({{ $openingNatureLabel }})</span></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ledger-stat-card">
                            <div class="text-muted fs-7">@lang('accounting::lang.total_debit')</div>
                            <div class="fw-bold fs-4">{{ number_format($periodDebit, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ledger-stat-card">
                            <div class="text-muted fs-7">@lang('accounting::lang.total_credit')</div>
                            <div class="fw-bold fs-4">{{ number_format($periodCredit, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ledger-stat-card">
                            <div class="text-muted fs-7">@lang('accounting::lang.Closing balance')</div>
                            <div class="fw-bold fs-4">{{ number_format(abs($closingBalance), 2) }} <span class="fs-7 text-muted">({{ $closingNatureLabel }})</span></div>
                        </div>
                    </div>
                </div>
                @php
                    $ledgerColShow = fn(string $k): bool => in_array($k, $ledger_visible_columns, true);
                    $ledgerPrefixKeys = ['ref_no', 'operation_date', 'transaction', 'cost_center', 'added_by'];
                    $ledgerOpeningLabelSpan = max(1, count(array_diff($ledger_visible_columns, ['balance'])));
                    $ledgerFootLabelSpan = max(1, count(array_intersect($ledgerPrefixKeys, $ledger_visible_columns)));
                @endphp
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4" id="ledger-table">
                        <thead>
                            <tr class="fw-bold  text-muted bg-light">
                                <th class="min-w-125px @if (!$ledgerColShow('ref_no')) d-none @endif" data-ledger-col="ref_no">
                                    @lang('accounting::lang.transaction_number')</th>
                                <th class="min-w-100px @if (!$ledgerColShow('operation_date')) d-none @endif" data-ledger-col="operation_date">
                                    @lang('accounting::lang.operation_date')</th>
                                <th class="min-w-125px @if (!$ledgerColShow('transaction')) d-none @endif" data-ledger-col="transaction">
                                    @lang('accounting::lang.transaction')</th>
                                <th class="min-w-125px @if (!$ledgerColShow('cost_center')) d-none @endif" data-ledger-col="cost_center">
                                    @lang('accounting::lang.cost_center')</th>
                                <th class="min-w-200px @if (!$ledgerColShow('added_by')) d-none @endif" data-ledger-col="added_by">
                                    @lang('accounting::lang.added_by')</th>
                                <th class="min-w-150px @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                    @lang('accounting::lang.debit')</th>
                                <th class="min-w-150px @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                    @lang('accounting::lang.credit')</th>
                                <th class="min-w-150px @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                    @lang('accounting::lang.balance')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $balance = $opening_balance;
                                $total_debit = 0;
                                $total_credit = 0;
                            @endphp

                            <tr class="ledger-row-opening">
                                <td class="text-center fw-bold ledger-opening-label" colspan="{{ $ledgerOpeningLabelSpan }}">
                                    @lang('accounting::lang.opening_balance')</td>
                                <td class="fw-bold ledger-opening-balance @if (!$ledgerColShow('balance')) d-none @endif" style="color:#020804"
                                    data-ledger-col="balance">
                                    @if ($balance < 0)
                                        ({{ number_format(abs($balance), 2) }})
                                    @else
                                        {{ number_format($balance, 2) }}
                                    @endif
                                </td>
                            </tr>

                            @foreach ($account_transactions as $transactions)
                                @php
                                    $account_type = $transactions->account->account_primary_type;
                                    $is_debit_nature = in_array($account_type, [
                                        'asset',
                                        'expenses',
                                        'analytical_accounts',
                                    ]);

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
                                @endphp
                                <tr>
                                    <td class="@if (!$ledgerColShow('ref_no')) d-none @endif" data-ledger-col="ref_no">
                                        <div class="d-flex align-items-center">
                                            <div class="d-flex justify-content-start flex-column">
                                                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6"
                                                    @if ($transactions->sub_type == 'sell' || $transactions->sub_type == 'purchases') href="{{ url("/transaction-show/{$transactions->transaction_id}") }}" @endif>
                                                    @if (isset($transactions->accTransMapping))
                                                        {{ $transactions->accTransMapping->ref_no }}
                                                    @elseif (isset($transactions->transaction))
                                                        {{ $transactions->transaction->ref_no }}
                                                    @else
                                                        --
                                                    @endif
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="@if (!$ledgerColShow('operation_date')) d-none @endif" data-ledger-col="operation_date">
                                        <a class="text-gray-900 fw-bold text-hover-primary d-block mb-1 fs-7">
                                            {{ \Carbon\Carbon::parse($transactions->operation_date)->format('d/m/Y h:i A') }}
                                        </a>
                                    </td>
                                    <td class="@if (!$ledgerColShow('transaction')) d-none @endif" data-ledger-col="transaction">
                                        <span class="badge badge-light-primary fs-7">
                                            @if ($transactions->sub_type == 'sell')
                                                @lang('accounting::lang.sell')
                                            @elseif ($transactions->sub_type == 'sell_cash')
                                                @lang('accounting::lang.receipt_voucher')
                                            @elseif ($transactions->sub_type == 'sales_revenue')
                                                @lang('accounting::lang.payment_voucher')
                                            @else
                                                @lang('accounting::lang.' . $transactions->sub_type)
                                            @endif
                                        </span>
                                    </td>
                                    <td class="@if (!$ledgerColShow('cost_center')) d-none @endif" data-ledger-col="cost_center">
                                        <span class="text-muted fw-semibold text-muted d-block fs-7">
                                            @if ($transactions->costCenter)
                                                {{ $transactions?->costCenter->account_center_number . ' - ' . (App::getLocale() == 'ar' ? $transactions->costCenter->name_ar : $transactions->costCenter->name_en) }}
                                            @else
                                                --
                                            @endif
                                        </span>
                                    </td>
                                    <td class="@if (!$ledgerColShow('added_by')) d-none @endif" data-ledger-col="added_by">
                                        <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6">
                                            {{ $transactions->createdBy->name }}
                                        </a>
                                    </td>
                                    <td class="@if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                        @if ($transactions->type == 'debit')
                                            <a class=" fw-bold text-hover-primary mb-1 fs-6" style="color: #020804">
                                                {{ number_format($transactions->amount, 2) }}
                                            </a>
                                        @else
                                            <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                                --
                                            </span>
                                        @endif
                                    </td>
                                    <td class="@if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                        @if ($transactions->type == 'credit')
                                            <a class=" fw-bold text-hover-primary mb-1 fs-6" style="color: #020804">
                                                {{ number_format($transactions->amount, 2) }}
                                            </a>
                                        @else
                                            <span class="text-muted fw-semibold text-muted d-block fs-4 mt-1">
                                                --
                                            </span>
                                        @endif
                                    </td>
                                    <td class="@if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                        <a class=" fw-bold text-hover-primary mb-1 fs-6" style="color: #020804">
                                            {{ number_format(abs($balance), 2) }}
                                            <span class="fs-8 text-muted">({{ $balance < 0 ? __('accounting::lang.credit') : __('accounting::lang.debit') }})</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-center fw-bold fs-4 ledger-foot-label" colspan="{{ $ledgerFootLabelSpan }}">
                                    @lang('accounting::lang.Closing balance')</td>
                                <td class=" fw-bold fs-5 ledger-foot-debit @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                    @format_currency($total_debit)
                                </td>
                                <td class=" fw-bold fs-5 ledger-foot-credit @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                    @format_currency($total_credit)
                                </td>
                                <td class=" fw-bold fs-5 ledger-foot-balance @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                    @format_currency(abs($balance))
                                    <span class="fs-8 text-muted">({{ $balance < 0 ? __('accounting::lang.credit') : __('accounting::lang.debit') }})</span>
                                </td>
                            </tr>
                        </tfoot>


                    </table>


                </div>

            </div>
            {{ $account_transactions->appends(['account_id' => request('account_id')])->links('pagination::bootstrap-4') }}

        @endif
    </div>
@endsection
@section('script')


    <script>
        $(document).ready(function() {
            var LEDGER_COL_ORDER = @json(\Modules\Accounting\Http\Controllers\TreeAccountsController::LEDGER_COLUMN_ORDER);
            var LEDGER_COL_STORAGE = 'ledger_visible_columns_v1';
            var ledgerVisibleFromServer = @json($ledger_visible_columns);

            function ledgerReadVisibleFromStorage() {
                try {
                    var raw = localStorage.getItem(LEDGER_COL_STORAGE);
                    if (!raw) {
                        return null;
                    }
                    var parsed = JSON.parse(raw);
                    if (!Array.isArray(parsed)) {
                        return null;
                    }
                    var set = {};
                    parsed.forEach(function(k) {
                        set[k] = true;
                    });
                    if (!set.balance) {
                        parsed.push('balance');
                    }
                    return LEDGER_COL_ORDER.filter(function(k) {
                        return parsed.indexOf(k) !== -1;
                    });
                } catch (e) {
                    return null;
                }
            }

            function ledgerWriteVisible(cols) {
                var ordered = LEDGER_COL_ORDER.filter(function(k) {
                    return cols.indexOf(k) !== -1;
                });
                if (ordered.indexOf('balance') === -1) {
                    ordered.push('balance');
                }
                localStorage.setItem(LEDGER_COL_STORAGE, JSON.stringify(ordered));
                return ordered;
            }

            function ledgerColsQueryString(cols) {
                return 'ledger_cols=' + encodeURIComponent(cols.join(','));
            }

            function ledgerUpdateExportLinks(cols) {
                var q = ledgerColsQueryString(cols);
                $('.ledger-export-link').each(function() {
                    var base = $(this).data('ledger-export-base');
                    if (base) {
                        $(this).attr('href', base + (base.indexOf('?') >= 0 ? '&' : '?') + q);
                    }
                });
            }

            function ledgerApplyColumnVisibility(cols) {
                var vis = {};
                cols.forEach(function(k) {
                    vis[k] = true;
                });
                LEDGER_COL_ORDER.forEach(function(k) {
                    var on = !!vis[k];
                    $('#ledger-table').find('[data-ledger-col="' + k + '"]').toggleClass('d-none', !on);
                });
                var openingSpan = Math.max(1, cols.filter(function(k) {
                    return k !== 'balance';
                }).length);
                $('.ledger-opening-label').attr('colspan', openingSpan);
                var prefixKeys = ['ref_no', 'operation_date', 'transaction', 'cost_center', 'added_by'];
                var footSpan = Math.max(1, cols.filter(function(k) {
                    return prefixKeys.indexOf(k) !== -1;
                }).length);
                $('.ledger-foot-label').attr('colspan', footSpan);
                ledgerUpdateExportLinks(cols);
            }

            function ledgerSyncCheckboxes(cols) {
                $('.ledger-col-cb').each(function() {
                    var key = $(this).data('ledger-col-key');
                    if (key === 'balance') {
                        $(this).prop('checked', true);
                        return;
                    }
                    $(this).prop('checked', cols.indexOf(key) !== -1);
                });
            }

            var urlHasLedgerCols = @json(request()->filled('ledger_cols'));
            var initialCols = urlHasLedgerCols ? ledgerVisibleFromServer : (ledgerReadVisibleFromStorage() || ledgerVisibleFromServer);
            initialCols = ledgerWriteVisible(initialCols);
            ledgerSyncCheckboxes(initialCols);
            ledgerApplyColumnVisibility(initialCols);

            $('.ledger-col-cb').on('change', function() {
                var key = $(this).data('ledger-col-key');
                if (key === 'balance') {
                    return;
                }
                var next = [];
                $('.ledger-col-cb').each(function() {
                    var k = $(this).data('ledger-col-key');
                    if (k === 'balance' || $(this).is(':checked')) {
                        next.push(k);
                    }
                });
                if (next.indexOf('balance') === -1) {
                    next.push('balance');
                }
                next = ledgerWriteVisible(next);
                ledgerApplyColumnVisibility(next);
            });

            var ledgerAccountId = String({{ (int) $account->id }});
            var ledgerBaseUrl = @json(url('ledger'));
            var swalTitle = @json(__('accounting::lang.ledger_switch_confirm_title'));
            var swalIntro = @json(__('accounting::lang.ledger_switch_confirm_intro'));
            var swalOk = @json(__('accounting::lang.ledger_switch_confirm_ok'));
            var swalCancel = @json(__('accounting::lang.ledger_switch_confirm_cancel'));

            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, function(c) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c];
                });
            }

            function confirmLedgerSwitch(newId, accountLabel) {
                var doGo = function() {
                    window.location.href = ledgerBaseUrl + '?account_id=' + encodeURIComponent(newId);
                };
                var SwalApi = (typeof window.Swal !== 'undefined' && window.Swal) ? window.Swal :
                    (typeof window.Sweetalert2 !== 'undefined' ? window.Sweetalert2 : null);
                if (SwalApi && typeof SwalApi.fire === 'function') {
                    SwalApi.fire({
                        title: swalTitle,
                        html: '<p class="mb-2">' + escapeHtml(swalIntro) + '</p><p class="fw-bold fs-5">' + escapeHtml(accountLabel) + '</p>',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: swalOk,
                        cancelButtonText: swalCancel,
                        reverseButtons: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }}
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            doGo();
                        }
                    });
                } else if (window.confirm(swalIntro + '\n' + accountLabel)) {
                    doGo();
                }
            }

            $('#accounts').select2();

            $('#accounts').on('select2:selecting', function(e) {
                var data = e.params && e.params.args && e.params.args.data ? e.params.args.data :
                    (e.params && e.params.data ? e.params.data : null);
                if (!data) {
                    return;
                }
                var newId = String(data.id);
                if (newId === ledgerAccountId) {
                    return;
                }
                e.preventDefault();
                confirmLedgerSwitch(newId, data.text || '');
            });

            $('#accounts').on('change', function() {
                if ($('#accounts').data('select2')) {
                    return;
                }
                var sel = this;
                var v = String(sel.value);
                if (v === ledgerAccountId) {
                    return;
                }
                var label = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '';
                sel.value = ledgerAccountId;
                confirmLedgerSwitch(v, label);
            });

            $('#choose_cost_center_select').select2();
        });
    </script>
@stop
