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

        .ledger-report-banner {
            border: 1px solid #e4e6ef;
            border-radius: 12px;
            background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .ledger-report-banner .ledger-report-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #181c32;
            letter-spacing: -0.02em;
        }

        .ledger-report-banner .ledger-report-account {
            font-size: 1rem;
            color: #3f4254;
            margin-top: 0.25rem;
        }

        .ledger-report-banner .ledger-report-period {
            font-size: 0.9rem;
            color: #7e8299;
            margin-top: 0.35rem;
        }

        .ledger-report-banner .ledger-report-class {
            font-size: 0.85rem;
            color: #5e6278;
            margin-top: 0.5rem;
        }

        .ledger-banner-controls {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: end;
            justify-content: flex-end;
        }

        .ledger-banner-controls .select2-container {
            min-width: 320px;
        }

        #ledger_sub_type_filter + .select2-container .select2-selection--multiple {
            min-height: 44px;
        }

        @media (max-width: 992px) {
            .ledger-banner-controls {
                justify-content: flex-start;
            }

            .ledger-banner-controls .select2-container {
                min-width: 100%;
            }
        }

        #ledger-table.ledger-table-pro {
            border: 1px solid #e4e6ef;
            font-variant-numeric: tabular-nums;
        }

        #ledger-table.ledger-table-pro thead th {
            background: #f5f8fa !important;
            color: #3f4254 !important;
            font-size: 0.86rem;
            font-weight: 800;
            text-transform: none;
            letter-spacing: 0;
            border-bottom: 2px solid #e4e6ef;
            vertical-align: middle;
            text-align: center;
            padding-top: 0.65rem;
            padding-bottom: 0.65rem;
            padding-inline: 0.9rem;
        }

        #ledger-table.ledger-table-pro tbody td,
        #ledger-table.ledger-table-pro tfoot td {
            border-color: #eff2f5;
            vertical-align: middle;
            padding-top: 0.45rem;
            padding-bottom: 0.45rem;
            padding-inline: 0.9rem;
        }

        #ledger-table.ledger-table-pro .ledger-num {
            text-align: end;
            white-space: nowrap;
        }

        #ledger-table.ledger-table-pro td {
            font-size: 0.78rem;
        }

        #ledger-table.ledger-table-pro thead th {
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
            padding-inline: 0.9rem;
        }

        .ledger-balance-pill {
            display: inline-flex;
            align-items: baseline;
            font-weight: 700;
        }

        .ledger-balance-pill.ledger-bal-dr {
            color: #0f5132;
        }

        .ledger-balance-pill.ledger-bal-cr {
            color: #b02a37;
        }

        #ledger-table.ledger-table-pro .ledger-row-opening td {
            background: #f8f9fb;
        }

        #ledger-table.ledger-table-pro tfoot tr td {
            background: #f0f4f8;
            border-top: 2px solid #e4e6ef;
        }

        /* Metronic `.table.gs-0` zeros first/last cell horizontal padding — restore breathing room */
        #ledger-table.ledger-table-pro thead th:first-child,
        #ledger-table.ledger-table-pro tbody td:first-child,
        #ledger-table.ledger-table-pro tfoot td:first-child {
            padding-left: 0.9rem !important;
        }

        #ledger-table.ledger-table-pro thead th:last-child,
        #ledger-table.ledger-table-pro tbody td:last-child,
        #ledger-table.ledger-table-pro tfoot td:last-child {
            padding-right: 0.9rem !important;
        }
    </style>
@stop
@section('content')
    <div class="container">
        <div class="ledger-report-banner">
            <div class="row align-items-start g-3">
                <div class="col-lg-8">
                    <div class="ledger-report-title">@lang('accounting::lang.ledger')</div>
                    <div class="ledger-report-account">
                        <span class="fw-bold text-gray-800">{{ $account->gl_code }}</span>
                        <span class="text-gray-500">—</span>
                        {{ app()->getLocale() == 'ar' ? $account->name_ar : $account->name_en }}
                    </div>

                    <div class="mt-3">
                        <div class="ledger-banner-controls">
                            <div style="flex: 1 1 auto;">
                                <label class="form-label fs-7 text-muted mb-1">@lang('accounting::lang.account_name')</label>
                                <select id="accounts" class="form-select form-select-solid select-2 w-100" name="id">
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
                            <div>
                                <label class="form-label fs-7 text-muted mb-1 d-block">&nbsp;</label>
                                <button type="button" id="ledgerAccountSearchBtn" class="btn btn-primary px-6">
                                    {{ app()->getLocale() == 'ar' ? 'بحث' : 'Search' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="ledger-report-period">
                        {{ __('accounting::lang.ledger_report_period', [
                            'from' => \Carbon\Carbon::parse($start_date)->format('d/m/Y'),
                            'to' => \Carbon\Carbon::parse($end_date)->format('d/m/Y'),
                        ]) }}
                    </div>
                    <div class="ledger-report-class">
                        @lang('accounting::lang.ledger_report_account_class'):
                        @lang('accounting::lang.' . $account->account_primary_type)
                        @if ($account->account_sub_type)
                            <span class="text-gray-500">·</span>
                            {{ app()->getLocale() == 'ar' ? $account->account_sub_type['name_ar'] : $account->account_sub_type['name_en'] }}
                        @endif
                    </div>
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

            <div class="col-md-3 mt-4">
                <label>{{ __('accounting::lang.transaction_type') }}</label>
                <select name="sub_type[]" id="ledger_sub_type_filter" class="form-select form-select-solid" multiple
                    data-placeholder="{{ __('accounting::lang.all') }}">
                    @foreach ($subTypes ?? [] as $subTypeVal)
                        <option value="{{ $subTypeVal }}" @selected(in_array((string) $subTypeVal, $ledgerSelectedSubTypes ?? [], true))>
                            {{ \Illuminate\Support\Facades\Lang::has('accounting::lang.' . $subTypeVal) ? __('accounting::lang.' . $subTypeVal) : $subTypeVal }}
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
                                        'narration' => __('accounting::lang.ledger_column_narration'),
                                        'cost_center' => __('accounting::lang.ledger_column_cost_center'),
                                        'added_by' => __('accounting::lang.ledger_column_added_by'),
                                    ];
                                    $ledgerExportQuery = array_merge($ledger_export_base_params ?? [], ['ledger_cols' => implode(',', $ledger_visible_columns)]);
                                @endphp
                                @foreach (array_keys($ledgerColMeta) as $colKey)
                                    <div class="form-check">
                                        <input class="form-check-input ledger-col-cb" type="checkbox"
                                            id="ledger-col-{{ $colKey }}" data-ledger-col-key="{{ $colKey }}"
                                            @if (in_array($colKey, $ledger_visible_columns, true)) checked @endif>
                                        <label class="form-check-label fs-8" for="ledger-col-{{ $colKey }}"
                                            >{{ $ledgerColMeta[$colKey] }}</label>
                                    </div>
                                @endforeach
                            </li>

                            <li>
                                <div class="menu-item-custom ">
                                    <a href="{{ url('/print-ledger', $account->id) }}?{{ http_build_query($ledgerExportQuery) }}"
                                        class="btn ledger-export-link"
                                        data-ledger-export-base="{{ url('/print-ledger', $account->id) }}">@lang('accounting::fields.print')</a>
                                </div>
                            </li>

                            <li>
                                <div class="menu-item-custom ">
                                    <a href="{{ url('/ledger-export-pdf', $account->id) }}?{{ http_build_query($ledgerExportQuery) }}"
                                        class="btn btn-export-pdf ledger-export-link"
                                        data-ledger-export-base="{{ url('/ledger-export-pdf', $account->id) }}">@lang('general.export_as_pdf')</a>
                                </div>
                            </li>

                            <li>
                                <div class="menu-item-custom ">
                                    <a href="{{ url('/ledger-export-excel', $account->id) }}?{{ http_build_query($ledgerExportQuery) }}"
                                        class="btn btn-export-excel ledger-export-link"
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
                    $ledgerPrefixKeys = ['ref_no', 'operation_date', 'narration', 'cost_center', 'added_by'];
                    $ledgerOpeningLabelSpan = max(1, count(array_diff($ledger_visible_columns, ['balance'])));
                    $ledgerFootLabelSpan = max(1, count(array_intersect($ledgerPrefixKeys, $ledger_visible_columns)));
                @endphp
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4 ledger-table-pro" id="ledger-table">
                        <thead>
                            <tr class="fw-bold  text-muted bg-light">
                                <th class="min-w-110px @if (!$ledgerColShow('ref_no')) d-none @endif" data-ledger-col="ref_no">
                                    @lang('accounting::lang.transaction_number')</th>
                                <th class="min-w-90px @if (!$ledgerColShow('operation_date')) d-none @endif" data-ledger-col="operation_date">
                                    @lang('accounting::lang.operation_date')</th>
                                <th class="min-w-140px @if (!$ledgerColShow('narration')) d-none @endif" data-ledger-col="narration">
                                    @lang('accounting::lang.ledger_narration')</th>
                                <th class="min-w-120px @if (!$ledgerColShow('cost_center')) d-none @endif" data-ledger-col="cost_center">
                                    @lang('accounting::lang.cost_center')</th>
                                <th class="min-w-120px @if (!$ledgerColShow('added_by')) d-none @endif" data-ledger-col="added_by">
                                    @lang('accounting::lang.added_by')</th>
                                <th class="min-w-110px ledger-num @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                    @lang('accounting::lang.debit')</th>
                                <th class="min-w-110px ledger-num @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                    @lang('accounting::lang.credit')</th>
                                <th class="min-w-120px ledger-num @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
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
                                <td class="fw-bold ledger-opening-balance ledger-num @if (!$ledgerColShow('balance')) d-none @endif" style="color:#020804"
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
                                        <span class="text-gray-900 fw-semibold fs-7">
                                            {{ \Carbon\Carbon::parse($transactions->operation_date)->format('d/m/Y') }}
                                        </span>
                                    </td>
                                    <td class="@if (!$ledgerColShow('narration')) d-none @endif" data-ledger-col="narration">
                                        @php
                                            $ledgerNarr = trim((string) ($transactions->note ?? ''));
                                            if ($ledgerNarr === '' && $transactions->accTransMapping && $transactions->accTransMapping->note) {
                                                $ledgerNarr = trim((string) $transactions->accTransMapping->note);
                                            }
                                        @endphp
                                        <span class="text-gray-800 fs-7">{{ $ledgerNarr !== '' ? $ledgerNarr : '—' }}</span>
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
                                    <td class="ledger-num @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                        @if ($transactions->type == 'debit')
                                            <span class="fw-bold fs-6" style="color: #020804">{{ number_format($transactions->amount, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="ledger-num @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                        @if ($transactions->type == 'credit')
                                            <span class="fw-bold fs-6" style="color: #020804">{{ number_format($transactions->amount, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="ledger-num @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                        @php
                                            $balIsCredit = $balance < 0;
                                            $balCss = $balIsCredit ? 'ledger-bal-cr' : 'ledger-bal-dr';
                                            $balHint = app()->getLocale() == 'ar'
                                                ? ($balIsCredit
                                                    ? 'رصيد دائن: يعني أن إجمالي الدائن أكبر من إجمالي المدين ضمن الفترة (مع احتساب الرصيد الافتتاحي). تم حساب الرصيد وفق طبيعة الحساب.'
                                                    : 'رصيد مدين: يعني أن إجمالي المدين أكبر من إجمالي الدائن ضمن الفترة (مع احتساب الرصيد الافتتاحي). تم حساب الرصيد وفق طبيعة الحساب.')
                                                : ($balIsCredit
                                                    ? 'Credit balance: total credit exceeds total debit for the period (including opening balance). Balance is computed according to the account nature.'
                                                    : 'Debit balance: total debit exceeds total credit for the period (including opening balance). Balance is computed according to the account nature.');
                                        @endphp
                                        <span class="ledger-balance-pill {{ $balCss }}" title="{{ $balHint }}">
                                            {{ number_format(abs($balance), 2) }}
                                        </span>
                                    </td>
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

                            <tr>
                                <td class="text-center fw-bold fs-6 ledger-foot-label" colspan="{{ $ledgerFootLabelSpan }}">
                                    <span title="{{ $totalsHint }}">{{ $totalsLabel }}</span>
                                </td>
                                <td class="fw-bold fs-6 ledger-foot-debit ledger-num @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                    @format_currency($total_debit)
                                </td>
                                <td class="fw-bold fs-6 ledger-foot-credit ledger-num @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                    @format_currency($total_credit)
                                </td>
                                <td class="fw-bold fs-6 ledger-foot-balance ledger-num @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                    <span class="text-muted">—</span>
                                </td>
                            </tr>

                            <tr>
                                <td class="text-center fw-bold fs-6 ledger-foot-label" colspan="{{ $ledgerFootLabelSpan }}">
                                    <span title="{{ $netHint }}">{{ $netLabel }}</span>
                                </td>
                                <td class="fw-bold fs-6 ledger-num @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                    @if (! $netMovementIsCredit)
                                        @format_currency($netMovementAbs)
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="fw-bold fs-6 ledger-num @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                    @if ($netMovementIsCredit)
                                        @format_currency($netMovementAbs)
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="fw-bold fs-6 ledger-num @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                    <span class="text-muted">—</span>
                                </td>
                            </tr>

                            @php
                                $footIsCredit = $balance < 0;
                                $footCss = $footIsCredit ? 'ledger-bal-cr' : 'ledger-bal-dr';
                                $footHint = app()->getLocale() == 'ar'
                                    ? ($footIsCredit
                                        ? 'الرصيد الختامي دائن: إجمالي الدائن أكبر من إجمالي المدين ضمن الفترة (مع الرصيد الافتتاحي).'
                                        : 'الرصيد الختامي مدين: إجمالي المدين أكبر من إجمالي الدائن ضمن الفترة (مع الرصيد الافتتاحي).')
                                    : ($footIsCredit
                                        ? 'Closing balance is credit: total credit exceeds total debit for the period (including opening balance).'
                                        : 'Closing balance is debit: total debit exceeds total credit for the period (including opening balance).');
                            @endphp
                            <tr>
                                <td class="text-center fw-bold fs-6 ledger-foot-label" colspan="{{ $ledgerFootLabelSpan }}">
                                    <span title="{{ $endingHint }}">{{ $endingLabel }}</span>
                                </td>
                                <td class="fw-bold fs-6 ledger-num @if (!$ledgerColShow('debit')) d-none @endif" data-ledger-col="debit">
                                    <span class="text-muted">—</span>
                                </td>
                                <td class="fw-bold fs-6 ledger-num @if (!$ledgerColShow('credit')) d-none @endif" data-ledger-col="credit">
                                    <span class="text-muted">—</span>
                                </td>
                                <td class="fw-bold fs-6 ledger-foot-balance ledger-num @if (!$ledgerColShow('balance')) d-none @endif" data-ledger-col="balance">
                                    <span class="ledger-balance-pill {{ $footCss }}" title="{{ $footHint }}">
                                        @format_currency(abs($balance))
                                    </span>
                                </td>
                            </tr>
                        </tfoot>


                    </table>


                </div>

            </div>
            {{ $account_transactions->withQueryString()->links('pagination::bootstrap-4') }}

        @endif
    </div>
@endsection
@section('script')


    <script>
        $(document).ready(function() {
            var LEDGER_COL_ORDER = ['ref_no', 'operation_date', 'narration', 'cost_center', 'added_by', 'debit', 'credit', 'balance'];
            var LEDGER_COL_STORAGE = 'ledger_visible_columns_v2';
            var ledgerVisibleFromServer = @json($ledger_visible_columns);
            var ledgerExportBaseParams = @json($ledger_export_base_params ?? []);

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
                if (parsed.indexOf('balance') === -1) parsed.push('balance');
                if (parsed.indexOf('debit') === -1) parsed.push('debit');
                if (parsed.indexOf('credit') === -1) parsed.push('credit');
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
                if (ordered.indexOf('debit') === -1) {
                    ordered.push('debit');
                }
                if (ordered.indexOf('credit') === -1) {
                    ordered.push('credit');
                }
                localStorage.setItem(LEDGER_COL_STORAGE, JSON.stringify(ordered));
                return ordered;
            }

            function ledgerUpdateExportLinks(cols) {
                var params = $.extend(true, {}, ledgerExportBaseParams, { ledger_cols: cols.join(',') });
                $('.ledger-export-link').each(function() {
                    var base = $(this).data('ledger-export-base');
                    if (base) {
                        $(this).attr('href', base + '?' + $.param(params));
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
                var prefixKeys = ['ref_no', 'operation_date', 'narration', 'transaction', 'cost_center', 'added_by'];
                var footSpan = Math.max(1, cols.filter(function(k) {
                    return prefixKeys.indexOf(k) !== -1;
                }).length);
                $('.ledger-foot-label').attr('colspan', footSpan);
                ledgerUpdateExportLinks(cols);
            }

            function ledgerSyncCheckboxes(cols) {
                $('.ledger-col-cb').each(function() {
                    var key = $(this).data('ledger-col-key');
                    if (key === 'balance' || key === 'debit' || key === 'credit') {
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
                if (key === 'balance' || key === 'debit' || key === 'credit') {
                    return;
                }
                var next = [];
                $('.ledger-col-cb').each(function() {
                    var k = $(this).data('ledger-col-key');
                    if (k === 'balance' || k === 'debit' || k === 'credit' || $(this).is(':checked')) {
                        next.push(k);
                    }
                });
                if (next.indexOf('balance') === -1) {
                    next.push('balance');
                }
                if (next.indexOf('debit') === -1) next.push('debit');
                if (next.indexOf('credit') === -1) next.push('credit');
                next = ledgerWriteVisible(next);
                ledgerApplyColumnVisibility(next);
            });

            var ledgerAccountId = String({{ (int) $account->id }});
            var ledgerBaseUrl = @json(url('ledger'));
            function ledgerNavigateToAccount(newId) {
                var url = new URL(ledgerBaseUrl, window.location.origin);
                var params = new URLSearchParams(window.location.search);
                params.set('account_id', String(newId));
                url.search = params.toString();
                window.location.href = url.toString();
            }

            $('#accounts').select2();

            $('#ledgerAccountSearchBtn').on('click', function() {
                var sel = document.getElementById('accounts');
                if (!sel) return;
                var v = String(sel.value || '');
                if (!v) return;
                if (v === ledgerAccountId) return;
                ledgerNavigateToAccount(v);
            });

            $('#choose_cost_center_select').select2();
            $('#ledger_sub_type_filter').select2({
                width: '100%',
                placeholder: $('#ledger_sub_type_filter').data('placeholder') || '',
                allowClear: true
            });
        });
    </script>
@stop
