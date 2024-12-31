@extends('employee::layouts.master')

@section('title', __('menuItemLang.adjustments'))

@section('content')
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link nav-link-adjustment justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#adjustment_tab">@lang('menuItemLang.adjustments')</a>
            </li>
            <li class="nav-item">
                <a @class([
                    'nav-link nav-link-adjustment-type justify-content-center text-active-gray-800',
                ]) data-bs-toggle="tab" href="#adjustment_type_tab">@lang('employee::general.adjustments_types')</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="adjustment_tab" role="tabpanel">
                <x-cards.card>
                    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                        <x-tables.table-header model="adjustment" :addButton="auth()->user()->hasDashboardPermission('employees.allowance_deduction.create')" url="adjustment/create"
                            module="employee" />
                    </x-cards.card-header>
                    <x-cards.card-body class="table-responsive">
                        <x-tables.table :columns=$adjustments_columns model="adjustment" module="employee" />
                    </x-cards.card-body>
                </x-cards.card>
            </div>
            <div class="tab-pane fade show" id="adjustment_type_tab" role="tabpanel">
                <x-cards.card>
                    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                        <x-tables.table-header model="adjustment_type" :addButton="auth()->user()->hasDashboardPermission('employees.allowance_deduction.create')" url="adjustment/create"
                            module="employee" />
                    </x-cards.card-header>
                    <x-cards.card-body class="table-responsive">
                        <x-tables.table :columns=$adjustments_types_columns model="adjustment_type" module="employee" />
                    </x-cards.card-body>
                </x-cards.card>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script>
        "use strict";
        let adjustmentDataTable;
        let adjustmentTypeDataTable;
        const adjustmentTable = $('#kt_adjustment_table');
        const adjustmentTypeTable = $('#kt_adjustment_type_table');
        const adjustmentDataUrl = '{{ route('adjustments.index') }}';
        const adjustmentTypeDataUrl = '{{ route('adjustment_types.index') }}';

        $(document).ready(function() {
            if (!adjustmentTable.length) return;
            initAdjustmentDatatable();
            initAdjustmentTypeDatatable()
            handleTableSearch();
        });

        function handleTableSearch() {
            const filterSearch = $('[data-kt-filter="search"]');
            filterSearch.on('keyup', function(e) {
                adjustmentDataTable.search(e.target.value).draw();
            });
        };

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl =
                `{{ url('/pos-role/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    '{{ __('employee::general.this_role') }}'),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        adjustmentDataTable.ajax.reload();
                    });
                }
            });
        });

        $(document).on('click', '.nav-link-adjustment-type', function() {
            adjustmentTypeDataTable.ajax.reload();
        });

        $(document).on('click', '.nav-link-adjustment', function() {
            adjustmentDataTable.ajax.reload();
        });

        function initAdjustmentDatatable() {
            adjustmentDataTable = $(adjustmentTable).DataTable({
                processing: true,
                serverSide: true,
                ajax: adjustmentDataUrl,
                info: false,
                columns: [{
                        data: 'id',
                        name: 'id',
                        className: 'text-start'
                    },
                    {
                        data: 'adjustment_type_name',
                        name: 'adjustment_type_name'
                    },
                    {
                        data: 'employee',
                        name: 'employee'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'amount_type',
                        name: 'amount_type'
                    },
                    {
                        data: 'applicable_date',
                        name: 'applicable_date'
                    },
                    {
                        data: 'apply_once',
                        name: 'apply_once'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
                }
            });
        };


        function initAdjustmentTypeDatatable() {
            adjustmentTypeDataTable = $(adjustmentTypeTable).DataTable({
                processing: true,
                serverSide: true,
                ajax: adjustmentTypeDataUrl,
                info: false,
                columns: [{
                        data: 'id',
                        name: 'id',
                        className: 'text-start'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'name_en',
                        name: 'name_en'
                    },
                    {
                        data: 'type',
                        name: 'type'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
                }
            });
        };
    </script>
@endsection
