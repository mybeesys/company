@extends('layouts.app')

@section('title', __('accounting::lang.trial_balance'))

@section('content')


    <style>
        .trial-balance-status {
            border: 1px solid #e9edf3;
            border-radius: 10px;
            background: #f8fafc;
            padding: 10px 14px;
            margin-bottom: 12px;
        }

        .trial-balance-status.is-balanced {
            border-color: #d1f2df;
            background: #f0fff6;
        }

        .trial-balance-status.is-unbalanced {
            border-color: #ffd4d4;
            background: #fff5f5;
        }

        .tb-cell-unbalanced {
            background: #fff0f0 !important;
            color: #b42318 !important;
            font-weight: 700;
        }
    </style>


    <section class="content-header py-3">
        <h1>@lang('accounting::lang.trial_balance')</h1>
    </section>

    <section class="content">
        @include('accounting::reports.partials.inventory_policy_notice')

        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="level_filter">{{ __('accounting::lang.account_level') }}:</label>
                            <select name="level_filter" id="level_filter" class="form-control" style="width:100%">
                                @foreach ($levelsArray as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="with_zero_balances">{{ __('accounting::lang.balance') }}:</label>
                            <select class="form-control" name="with_zero_balances" id="with_zero_balances"
                                style="padding: 2px;">
                                <option value="0" selected>{{ __('accounting::lang.without_zero_balances') }}</option>
                                <option value="1">{{ __('accounting::lang.with_zero_balances') }}</option>
                                <option value="2">{{ __('accounting::lang.zero_balances') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="classification">{{ __('accounting::lang.classification') }}:</label>
                            <select class="form-control select-2" name="classification" id="classification"
                                style="padding: 2px;">
                                <option value="0" selected>{{ __('accounting::lang.detailed') }}</option>
                                <option value="1">{{ __('accounting::lang.aggregated') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row py-2">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date_filter">{{ __('accounting::lang.from_date') }}:</label>
                            <input type="date" name="start_date_filter" id="start_date_filter" class="form-control"
                                value="{{ now()->startOfYear()->format('Y-m-d') }}"
                                placeholder="{{ __('lang_v1.select_start_date') }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date_filter">{{ __('accounting::lang.to_date') }}:</label>
                            <input type="date" name="end_date_filter" id="end_date_filter" class="form-control"
                                value="{{ now()->format('Y-m-d') }}" placeholder="{{ __('lang_v1.select_end_date') }}">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}:</label>
                            <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                                class="form-select d-flex form-select-solid" multiple>
                                @foreach ($costCenters as $costCenter)
                                    <option value="{{ $costCenter->id }}" >
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

                </div>

                <div class="row my-3">
                    <div class="col-md-9">
                        <div class="form-group">
                            <label for="choose_accounts_select">{{ __('accounting::lang.account') }}:</label>
                            <select name="choose_accounts_select[]" id="choose_accounts_select"
                                class="form-select d-flex form-select-solid" multiple>
                                @foreach ($accounts_array as $key => $value)
                                    <option value="{{ $key }}" selected>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button class="btn btn-info btn-xs pull-right btn-flat" onclick="dataTable.ajax.reload();"
                            style="margin-top: 24px; width: 70px; height: 40px; border-radius: 8px;padding: 0;">@lang('report::general.filter')</button>
                        <button type="button" id="trialBalanceExportPdf" class="btn btn-export-pdf btn-sm">PDF</button>
                        <button type="button" id="trialBalanceExportExcel" class="btn btn-export-excel btn-sm">Excel</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="box box-warning">
            <div class="box-header with-border text-center my-3">
                <h2 class="box-title ">@lang('accounting::lang.trial_balance')</h2>
                {{-- <p>{{ @format_date($start_date) }} ~ {{ @format_date($end_date) }}</p> --}}
            </div>

            <div class="box-body">
                <div id="trial-balance-status" class="trial-balance-status">
                    <strong id="trial-balance-status-label">-</strong>
                    <span class="mx-2">|</span>
                    <span id="trial-balance-diff-label">-</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle  table-row-bordered fs-6 gy-5" id="kt_accounts_table">
                        <thead>
                            <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0 w-100"
                                style="background: rgb(219 226 247);">
                                <th colspan="2"></th>
                                <td colspan="1">@lang('accounting::lang.opening_balance')</td>
                                <td colspan="2">@lang('accounting::lang.accounting_transactions')</td>
                                <td colspan="2">@lang('accounting::lang.closing_balance')</td>
                                <th colspan="2"></th>

                            </tr>
                            <tr id="accounts_headerRow">
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.number')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.name')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.debit')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.credit')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.debit')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.credit')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.debit')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.credit')</th>
                                <th class="text-center align-middle min-w-125px">@lang('messages.actions')</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-600" id="accounts_tableBody">
                            <!-- Table body rows will be dynamically generated here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-center">@lang('accounting::lang.total'):</th>
                                <th id="debitOpeningTotal" class="debit_opening_total"></th>
                                <th id="creditOpeningTotal" class="credit_opening_total"></th>
                                <th id="debitTotal" class="debit_total"></th>
                                <th id="creditTotal" class="credit_total"></th>
                                <th id="closingDebitTotal" class="closing_debit_total"></th>
                                <th id="closingCreditTotal" class="closing_credit_total"></th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="2" class="text-center">@lang('accounting::lang.total_for_all_pages'):</th>
                                <th id="allpagesdebitOpeningTotal" class="all_pages_debit_opening_total"></th>
                                <th id="allpagescreditOpeningTotal" class="all_pages_credit_opening_total"></th>
                                <th id="allpagesdebitTotal" class="all_pages_debit_total"></th>
                                <th id="allpagescreditTotal" class="all_pages_credit_total"></th>
                                <th id="allpagesclosingDebitTotal" class="all_pages_closing_debit_total"></th>
                                <th id="allpagesclosingCreditTotal" class="all_pages_closing_credit_total"></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="modal fade" id="printledger" tabindex="-1" role="dialog"></div>
                </div>


            </div>

        </div>

    </section>


@stop

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_accounts_table');
        const dataUrl = '{{ route('trial-balance') }}';
        const exportPdfUrl = '{{ route('trial-balance-export-pdf') }}';
        const exportExcelUrl = '{{ route('trial-balance-export-excel') }}';
        const statusBox = $('#trial-balance-status');
        const statusLabel = $('#trial-balance-status-label');
        const diffLabel = $('#trial-balance-diff-label');

        function formatAmount(value) {
            return Number(value || 0).toFixed(2);
        }

        function renderBalanceStatus(debitTotal, creditTotal) {
            const diff = Math.abs((Number(debitTotal) || 0) - (Number(creditTotal) || 0));
            const isBalanced = diff < 0.005;

            statusBox.removeClass('is-balanced is-unbalanced');

            if (isBalanced) {
                statusBox.addClass('is-balanced');
                statusLabel.text("{{ __('accounting::lang.balance') }}: {{ __('accounting::lang.balanced') }}");
                diffLabel.text("{{ __('accounting::lang.difference') }}: " + formatAmount(0));
            } else {
                statusBox.addClass('is-unbalanced');
                statusLabel.text("{{ __('accounting::lang.balance') }}: {{ __('accounting::lang.unbalanced') }}");
                diffLabel.text("{{ __('accounting::lang.difference') }}: " + formatAmount(diff));
            }

            $('#allpagesclosingDebitTotal, #allpagesclosingCreditTotal').toggleClass('tb-cell-unbalanced', !isBalanced);
            $('#closingDebitTotal, #closingCreditTotal').toggleClass('tb-cell-unbalanced', !isBalanced);
        }

        function buildReportQuery() {
            const params = new URLSearchParams();

            if ($('#start_date_filter').val()) params.append('start_date', $('#start_date_filter').val());
            if ($('#end_date_filter').val()) params.append('end_date', $('#end_date_filter').val());
            if ($('#classification').val()) params.append('aggregated', $('#classification').val());
            if ($('#level_filter').val()) params.append('level_filter', $('#level_filter').val());
            if ($('#with_zero_balances').val()) params.append('with_zero_balances', $('#with_zero_balances').val());

            ($('#choose_accounts_select').val() || []).forEach(function(value) {
                params.append('choose_accounts_select[]', value);
            });

            ($('#choose_cost_center_select').val() || []).forEach(function(value) {
                params.append('choose_cost_center_select[]', value);
            });

            return params.toString();
        }

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();

            $('#classification').select2();
            $('#with_zero_balances').select2();
            $('#level_filter').select2();
            $('#choose_accounts_select').select2();
            $('#choose_cost_center_select').select2();



            $('#level_filter,#end_date_filter,#start_date_filter,#with_zero_balances,#classification')
                .on('change',
                    function() {
                        dataTable.ajax.reload();
                    });

            $('#start_date_filter, #end_date_filter').trigger('change');

            $('#trialBalanceExportPdf').on('click', function() {
                const query = buildReportQuery();
                window.open(exportPdfUrl + '?' + query, '_blank');
            });

            $('#trialBalanceExportExcel').on('click', function() {
                const query = buildReportQuery();
                window.location.href = exportExcelUrl + '?' + query;
            });

        });

        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: dataUrl,
                    data: function(d) {
                        if ($('#start_date_filter').val()) {
                            d.start_date = $('#start_date_filter').val();
                        }
                        if ($('#end_date_filter').val()) {
                            d.end_date = $('#end_date_filter').val();
                        }
                        if ($('#classification').val()) {
                            d.aggregated = $('#classification').val();
                        }
                        if ($('#level_filter').val()) {
                            d.level_filter = $('#level_filter').val();
                        }
                        if ($('#with_zero_balances').val()) {
                            d.with_zero_balances = $('#with_zero_balances').val();
                        }
                        if ($('#choose_accounts_select').val()) {
                            d.choose_accounts_select = $('#choose_accounts_select').val();
                        }
                        if ($('#choose_cost_center_select').val()) {
                            d.choose_cost_center_select = $('#choose_cost_center_select').val();
                        }

                    }
                },

                info: false,

                columns: [{
                        data: 'gl_code',
                        name: 'gl_code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },


                    {
                        data: 'debit_opening_balance',
                        name: 'debit_opening_balance',
                        searchable: false
                    }, {
                        data: 'credit_opening_balance',
                        name: 'credit_opening_balance',
                        searchable: false
                    },

                    {
                        data: 'debit_balance',
                        name: 'debit_balance',
                        searchable: false
                    },
                    {
                        data: 'credit_balance',
                        name: 'credit_balance',
                        searchable: false
                    },
                    {
                        data: 'closing_debit_balance',
                        name: 'closing_debit_balance',
                        searchable: false
                    },
                    {
                        data: 'closing_credit_balance',
                        name: 'closing_credit_balance',
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],

                order: [],
                scrollX: true,
                pageLength: 10,

                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();
                    var debit_opening_total = api.column(2).data().reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                    var credit_opening_total = api.column(3).data().reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                    var debit_total = api.column(4).data().reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                    var credit_total = api.column(5, ).data().reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                    var closing_debit_total = api.column(6).data().reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                    var closing_credit_total = api.column(7).data().reduce(function(a, b) {
                        return parseFloat(a) + parseFloat(b);
                    }, 0);

                    $('.debit_opening_total').html(debit_opening_total.toFixed(2));
                    $('.credit_opening_total').html(credit_opening_total.toFixed(2));
                    $('.debit_total').html(debit_total.toFixed(2));
                    $('.credit_total').html(credit_total.toFixed(2));
                    $('.closing_debit_total').html(closing_debit_total.toFixed(2));
                    $('.closing_credit_total').html(closing_credit_total.toFixed(2));

                    var debit_opening_total_all = api.ajax.json().totalDebitOpeningBalance;
                    var credit_opening_total_all = api.ajax.json().totalCreditOpeningBalance;
                    var debit_total_all = api.ajax.json().totalDebitBalance;
                    var credit_total_all = api.ajax.json().totalCreditBalance;
                    var closing_debit_total_all = api.ajax.json().totalClosingDebitBalance;
                    var closing_credit_total_all = api.ajax.json().totalClosingCreditBalance;

                    $('.all_pages_debit_opening_total')
                        .html(debit_opening_total_all.toFixed(2));
                    $('.all_pages_credit_opening_total')
                        .html(credit_opening_total_all.toFixed(2));

                    $('.all_pages_debit_total').html(debit_total_all.toFixed(2));
                    $('.all_pages_credit_total').html(credit_total_all.toFixed(2));
                    $('.all_pages_closing_debit_total').html(closing_debit_total_all.toFixed(2));
                    $('.all_pages_closing_credit_total').html(closing_credit_total_all.toFixed(2));

                    renderBalanceStatus(closing_debit_total_all, closing_credit_total_all);
                },

                drawCallback: function() {
                    KTMenu.createInstances();
                }
            });
        }
    </script>

@stop
