@extends('layouts.app')

@section('title', __('expense::lang.nav_manage'))

@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }
    </style>
@endsection

@section('content')

    <div class="mb-6">
        <div class="text-muted">
            @lang('expense::lang.manage_intro')
        </div>
    </div>

    @if (! empty($overviewStats))
        <div class="row g-4 g-xl-5 mb-8">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-primary bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-element-11 fs-2x text-primary"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.manage_stat_records')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['count']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-success bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-chart-simple fs-2x text-success"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.manage_stat_net')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['net'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-warning bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-percentage fs-2x text-warning"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.manage_stat_tax')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['tax'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-info bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-wallet fs-2x text-info"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.manage_stat_gross')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['gross'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (empty($hasExpenses))
        <div class="card1 h-md-100 my-5" dir="ltr">
            <div class="card-body d-flex flex-column flex-center">
                <div class="mb-2 px-20" style="place-items: center;">
                    <div class="py-10 text-center">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-light-show w-200px" alt="">
                        <img src="/assets/media/illustrations/empty-content.svg" class="theme-dark-show w-200px" alt="">
                    </div>
                    <h4 class="fw-semibold text-gray-800 text-center lh-lg">
                        <span class="fw-bolder">@lang('messages.no_data_found')</span>
                        <br>
                        @lang('expense::lang.empty_manage_hint')
                    </h4>
                    <a href="{{ route('expenses.manage.create') }}"
                        class="btn btn-primary fv-row flex-md-root my-3 min-w-150px mw-250px">@lang('expense::general.add_expense')</a>
                </div>
            </div>
        </div>
    @else
        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="container px-0 w-100">
                    <div class="card-toolbar flex-column align-items-stretch gap-5 gap-md-6 py-2" data-kt-expense-table-toolbar="base">
                        <div class="d-flex flex-wrap gap-3 align-items-end w-100">
                            <div class="min-w-175px">
                                <label class="form-label fs-7 mb-1">@lang('expense::fields.category')</label>
                                <select id="expense_filter_category" class="form-select form-select-sm form-select-solid">
                                    <option value="all">@lang('accounting::lang.all')</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-200px flex-grow-1" style="max-width: 280px;">
                                <label class="form-label fs-7 mb-1">@lang('expense::lang.treasury_accounts')</label>
                                <select id="expense_filter_credit_accounts" class="form-select form-select-sm form-select-solid" multiple>
                                    @foreach ($treasuryAccounts as $acc)
                                        <option value="{{ $acc->id }}">{{ $acc->gl_code }} —
                                            {{ app()->getLocale() === 'ar' ? $acc->name_ar : $acc->name_en }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="min-w-140px">
                                <label class="form-label fs-7 mb-1">@lang('accounting::lang.from_date')</label>
                                <input type="date" id="expense_filter_date_from" class="form-control form-control-sm form-control-solid">
                            </div>
                            <div class="min-w-140px">
                                <label class="form-label fs-7 mb-1">@lang('accounting::lang.to_date')</label>
                                <input type="date" id="expense_filter_date_until" class="form-control form-control-sm form-control-solid">
                            </div>
                            <div class="form-check form-check-custom form-check-solid mb-1">
                                <input class="form-check-input" type="checkbox" id="expense_filter_attachments" value="1">
                                <label class="form-check-label fs-7" for="expense_filter_attachments">@lang('expense::lang.with_attachments_only')</label>
                            </div>
                            <button type="button" class="btn btn-sm btn-light-primary" id="expense_apply_filters">@lang('report::general.filter')</button>
                            <button type="button" class="btn btn-sm btn-light" id="expense_reset_filters">@lang('accounting::lang.clear_filters')</button>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 gap-md-5 w-100">
                            <div class="flex-grow-1 min-w-200px" style="max-width: 420px;">
                                <div class="d-flex align-items-center position-relative my-1">
                                    <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                                    <input type="text" data-kt-filter="search" class="form-control form-control-solid ps-12"
                                        placeholder="{{ __('expense::general.expense_search') }}" />
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3 ms-auto">
                                <button type="button"
                                    class="btn btn-light-primary d-flex flex-nowrap text-nowrap justify-content-center px-5 mw-250px"
                                    data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                    <i class="ki-duotone ki-exit-down fs-2"><span class="path1"></span><span class="path2"></span></i>
                                    @lang('general.report_export')
                                </button>
                                <x-tables.export-menu id="expense" />
                                <div id="kt_expense_table_buttons" class="d-none"></div>
                                <a href="{{ route('expenses.manage.create') }}" id="add_expense_button"
                                    class="btn btn-primary min-w-150px mw-250px">@lang('expense::general.add_expense')</a>
                            </div>
                        </div>
                    </div>
                </div>
            </x-cards.card-header>

            <x-cards.card-body class="table-responsive">
                <x-tables.table :columns="$columns" model="expense" module="expense" />
            </x-cards.card-body>
        </div>
    @endif

@endsection

@section('script')
    @parent
    @if (! empty($hasExpenses))
        <script src="{{ url('js/table.js') }}"></script>
        <script>
            "use strict";
            let dataTable;
            const table = $('#kt_expense_table');
            const dataUrl = '{{ route('expenses.manage') }}';

            $(document).ready(function() {
                if (!table.length) return;
                initDatatable();
                exportButtons([0, 1, 2, 3, 4, 5, 6, 7, 8, 9], '#kt_expense_table');
                handleSearchDatatable();

                $('#expense_filter_credit_accounts').select2({
                    width: '100%',
                    dir: document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr',
                    placeholder: @json(__('expense::lang.filter_treasury_placeholder')),
                    closeOnSelect: false,
                    minimumResultsForSearch: 0,
                });
                $('#expense_filter_category').select2({
                    width: '100%',
                    minimumResultsForSearch: 10,
                    dir: document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr',
                });

                function expenseFilterParams() {
                    return {
                        category_id: $('#expense_filter_category').val(),
                        credit_account_ids: $('#expense_filter_credit_accounts').val(),
                        date_from: $('#expense_filter_date_from').val(),
                        date_until: $('#expense_filter_date_until').val(),
                        with_attachments: $('#expense_filter_attachments').is(':checked') ? 1 : ''
                    };
                }

                $('#expense_apply_filters').on('click', function() {
                    dataTable.ajax.url(dataUrl + '?' + $.param(expenseFilterParams())).load();
                });
                $('#expense_reset_filters').on('click', function() {
                    $('#expense_filter_category').val('all').trigger('change');
                    $('#expense_filter_credit_accounts').val(null).trigger('change');
                    $('#expense_filter_date_from').val('');
                    $('#expense_filter_date_until').val('');
                    $('#expense_filter_attachments').prop('checked', false);
                    dataTable.ajax.url(dataUrl).load();
                });

                $(document).on('click', '.expense-manage-delete', function(e) {
                    e.preventDefault();
                    const url = $(this).data('url');
                    const SwalApi = (typeof window.Swal !== 'undefined' && window.Swal) ?
                        window.Swal :
                        (typeof window.Sweetalert2 !== 'undefined' ? window.Sweetalert2 : null);
                    const go = () => {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: { _method: 'DELETE', _token: @json(csrf_token()) },
                        }).done(function() {
                            dataTable.ajax.reload(null, false);
                        }).fail(function(xhr) {
                            const j = xhr.responseJSON || {};
                            alert(j.message || xhr.statusText);
                        });
                    };
                    if (SwalApi && SwalApi.fire) {
                        SwalApi.fire({
                            icon: 'warning',
                            title: @json(__('messages.are_you_sure')),
                            text: @json(__('accounting::lang.voucher_delete_confirm')),
                            showCancelButton: true,
                            confirmButtonText: @json(__('messages.delete')),
                            cancelButtonText: @json(__('messages.cancel')),
                            reverseButtons: {{ app()->getLocale() === 'ar' ? 'true' : 'false' }}
                        }).then((r) => {
                            if (r.isConfirmed) go();
                        });
                    } else if (confirm(@json(__('accounting::lang.voucher_delete_confirm')))) {
                        go();
                    }
                });
            });

            function initDatatable() {
                dataTable = $(table).DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: dataUrl,
                    info: false,
                    columns: [{
                            data: 'id',
                            name: 'id',
                        },
                        {
                            data: 'expense_date',
                            name: 'expense_date',
                        },
                        {
                            data: 'category',
                            name: 'category',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'credit_account',
                            name: 'credit_account',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'description',
                            name: 'description',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'net_amount',
                            name: 'net_amount',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'tax_amount',
                            name: 'tax_amount',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'gross_amount',
                            name: 'gross_amount',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'attachments',
                            name: 'attachments',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'created_at',
                            name: 'created_at',
                            orderable: false,
                            searchable: false,
                        },
                        {
                            data: 'actions',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                        },
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    scrollX: true,
                    pageLength: 10,
                    drawCallback: function() {
                        if (typeof KTMenu !== 'undefined') {
                            KTMenu.createInstances();
                        }
                    },
                });
            }
        </script>
    @endif
@endsection
