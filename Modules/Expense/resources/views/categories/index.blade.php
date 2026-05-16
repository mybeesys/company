@extends('layouts.app')

@section('title', __('expense::lang.categories_heading'))

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
            @lang('expense::lang.categories_intro')
        </div>
    </div>

    @if (! empty($overviewStats))
        <div class="row g-4 g-xl-5 mb-8">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-primary bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-category fs-2x text-primary"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.category_stat_total')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['categories']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-success bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-element-11 fs-2x text-success"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.category_stat_expenses')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['expenses']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-warning bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-chart-simple fs-2x text-warning"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.category_stat_net')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['net'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 bg-light-info bg-opacity-25">
                    <div class="card-body d-flex align-items-center gap-4 py-6 px-6 px-xl-7">
                        <span class="symbol symbol-50px flex-shrink-0 bg-white rounded-2 border border-gray-200">
                            <span class="symbol-label bg-transparent">
                                <i class="ki-outline ki-check-circle fs-2x text-info"></i>
                            </span>
                        </span>
                        <div class="d-flex flex-column min-w-0 flex-grow-1">
                            <span class="text-gray-700 fw-semibold fs-7 text-uppercase ls-1">@lang('expense::lang.category_stat_used')</span>
                            <span class="fs-2 fw-bold text-gray-900 lh-1 mt-1">{{ number_format($overviewStats['used']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (empty($hasCategories))
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
                        @lang('expense::lang.empty_categories_hint')
                    </h4>
                    <button type="button" class="btn btn-primary fv-row flex-md-root my-3 min-w-150px mw-250px expense-category-new-btn">
                        @lang('expense::general.add_category')
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="card card-flush">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                <div class="container px-0 w-100">
                    <div class="card-toolbar flex-wrap align-items-center justify-content-between gap-3 gap-md-5 w-100 py-2">
                        <div class="flex-grow-1 min-w-200px" style="max-width: 420px;">
                            <div class="d-flex align-items-center position-relative my-1">
                                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                                <input type="text" data-kt-filter="search" class="form-control form-control-solid ps-12"
                                    placeholder="{{ __('expense::general.category_search') }}" />
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <a href="{{ route('expenses.manage') }}" class="btn btn-light min-w-125px">@lang('expense::lang.nav_manage')</a>
                            <button type="button" class="btn btn-primary min-w-150px expense-category-new-btn">
                                @lang('expense::general.add_category')
                            </button>
                        </div>
                    </div>
                </div>
            </x-cards.card-header>

            <x-cards.card-body class="table-responsive">
                <x-tables.table :columns="$columns" model="expense_category" module="expense" />
            </x-cards.card-body>
        </div>
    @endif

    @include('expense::categories.partials.category-modal')

@endsection

@section('script')
    @parent
    <script src="{{ url('js/table.js') }}"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_expense_category_table');
        const dataUrl = '{{ route('expenses.categories.index') }}';
        const storeUrl = '{{ route('expenses.categories.store') }}';
        const updateBase = '{{ url('expenses/categories') }}';
        const defaultTitle = @json(__('expense::lang.add_category_heading'));
        const editTitle = @json(__('expense::lang.edit_category_heading'));

        function resetCategoryForm() {
            const form = document.getElementById('expense-category-form');
            if (!form) return;
            form.action = storeUrl;
            $('#expense_category_method').prop('disabled', true);
            $('#expense_category_id').val('');
            $('#expense_category_name').val('').removeClass('is-invalid');
            $('#expense_category_name_error').text('');
            $('#expense_category_modal_title').text(defaultTitle);
        }

        function openCategoryModalForEdit(id, name) {
            $('#expense-category-form').attr('action', updateBase + '/' + id);
            $('#expense_category_method').prop('disabled', false).val('PUT');
            $('#expense_category_id').val(id);
            $('#expense_category_name').val(name);
            $('#expense_category_modal_title').text(editTitle);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('expense-category-modal')).show();
        }

        $(document).ready(function() {
            $('.expense-category-new-btn').on('click', function() {
                resetCategoryForm();
                const modalEl = document.getElementById('expense-category-modal');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                modalEl.addEventListener('shown.bs.modal', function focusName() {
                    document.getElementById('expense_category_name')?.focus();
                    modalEl.removeEventListener('shown.bs.modal', focusName);
                });
            });

            $('#expense-category-modal').on('hidden.bs.modal', resetCategoryForm);

            $('#expense-category-form').on('submit', function(e) {
                e.preventDefault();
                const $form = $(this);
                const isEdit = $('#expense_category_method').val() === 'PUT';
                const payload = {
                    name: $('#expense_category_name').val(),
                    _token: @json(csrf_token()),
                };
                if (isEdit) {
                    payload._method = 'PUT';
                }
                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: payload,
                }).done(function() {
                    bootstrap.Modal.getInstance(document.getElementById('expense-category-modal'))?.hide();
                    if (table.length && dataTable) {
                        dataTable.ajax.reload(null, false);
                    } else {
                        window.location.reload();
                    }
                }).fail(function(xhr) {
                    const j = xhr.responseJSON || {};
                    const errors = j.errors || {};
                    if (errors.name && errors.name[0]) {
                        $('#expense_category_name').addClass('is-invalid');
                        $('#expense_category_name_error').text(errors.name[0]);
                    } else {
                        alert(j.message || xhr.statusText);
                    }
                });
            });

            if (!table.length) return;

            initDatatable();
            exportButtons([0, 1, 2, 3], '#kt_expense_category_table');
            handleSearchDatatable();

            $(document).on('click', '.expense-category-edit', function(e) {
                e.preventDefault();
                openCategoryModalForEdit($(this).data('id'), $(this).data('name'));
            });

            $(document).on('click', '.expense-category-delete', function(e) {
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
                        data: 'category_name',
                        name: 'category_name',
                    },
                    {
                        data: 'expenses_count',
                        name: 'expenses_count',
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'net_total',
                        name: 'net_total',
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
                    [1, 'asc']
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
@endsection
