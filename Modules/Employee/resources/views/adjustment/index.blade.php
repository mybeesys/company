@extends('employee::layouts.master')

@section('title', __('menuItemLang.adjustments'))

@section('content')
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item">
                <a class="nav-link nav-link-adjustment justify-content-center text-active-gray-800 active"
                    data-bs-toggle="tab" href="#adjustment_tab">@lang('menuItemLang.adjustments')</a>
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
                        <x-tables.table-header :search="false" model="adjustment" :addButton="auth()->user()->hasDashboardPermission('employees.allowance_deduction.create')" module="employee">
                            <x-slot:filters>
                                <x-tables.filters-dropdown submitButtonClass="adjustment_submit_button"
                                    resetButtonClass="adjustment_reset_button">
                                    <x-employee::adjustments.filters />
                                </x-tables.filters-dropdown>
                            </x-slot:filters>
                        </x-tables.table-header>
                    </x-cards.card-header>
                    <x-cards.card-body class="table-responsive">
                        <x-tables.table :columns=$adjustments_columns model="adjustment" module="employee" />
                    </x-cards.card-body>
                </x-cards.card>
            </div>
            <div class="tab-pane fade show" id="adjustment_type_tab" role="tabpanel">
                <x-cards.card>
                    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
                        <x-tables.table-header :search="false" model="adjustment_type" :addButton="auth()->user()->hasDashboardPermission('employees.allowance_deduction.create')"
                            module="employee">
                            <x-slot:filters>
                                <x-tables.filters-dropdown submitButtonClass="adjustment_type_submit_button"
                                    resetButtonClass="adjustment_type_reset_button">
                                    <x-employee::adjustments.types-filters />
                                </x-tables.filters-dropdown>
                            </x-slot:filters>
                        </x-tables.table-header>
                    </x-cards.card-header>
                    <x-cards.card-body class="table-responsive">
                        <x-tables.table :columns=$adjustments_types_columns model="adjustment_type" module="employee" />
                    </x-cards.card-body>
                </x-cards.card>
            </div>
        </div>
    </div>
    <x-employee::adjustments.add-adjustment-modal :employees=$employees />
    <x-employee::adjustments.add-adjustment-type-modal />
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="vfs_fonts.js"></script>
    <script src="{{ url('/modules/employee/js/adjustments.js') }}"></script>
    <script src="{{ url('/modules/employee/js/adjustment-index.js') }}"></script>
    <script src="{{ url('/modules/employee/js/adjustment-type-index.js') }}"></script>
    <script>
        "use strict";
        let adjustmentDataTable;
        let adjustmentTypeDataTable;
        let lang = "{{ session('locale') }}"
        const adjustmentTable = $('#kt_adjustment_table');
        const adjustmentTypeTable = $('#kt_adjustment_type_table');
        const adjustmentDataUrl = '{{ route('adjustments.index') }}';
        const adjustmentTypeDataUrl = '{{ route('adjustment_types.index') }}';
        let adjustmentType_type;

        $(document).ready(function() {
            if (!adjustmentTable.length) return;
            initAdjustmentDatatable();
            initAdjustmentTypeDatatable();
            addAdjustmentForm("{{ route('adjustments.store') }}");
            addAdjustmentTypeForm("{{ route('adjustment_types.store') }}");

            $('#add_adjustment_button').on('click', function(e) {
                e.preventDefault();
                $('#add_adjustment_modal_form input, select').each(function() {
                    if ($(this).is(':checkbox, :radio')) {
                        $(this).prop('checked', false);
                    } else if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).val(null).trigger('change');
                    } else {
                        $(this).val(null);
                    }
                });
                $('select[name="adjustment_type"]').attr('disabled', 'disabled');
                $('select[name="adjustment_type"]').val(null).trigger('change');

                $('#add_adjustment_modal').modal('toggle');
            });

            $('#add_adjustment_type_button').on('click', function(e) {
                e.preventDefault();
                $('#add_adjustment_type_modal_form input, select').each(function() {
                    if ($(this).is(':checkbox, :radio')) {
                        $(this).prop('checked', false);
                    } else if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).val(null).trigger('change');
                    } else {
                        $(this).val(null);
                    }
                });
                $('select[name="adjustment_type_type"]').val(null).trigger('change');
                $('#add_adjustment_type_modal').modal('toggle');
            });

            $('select[name="adjustment_type"], select[name="adjustment_type_type"], select[name="amount_type"], select[name="type"]')
                .select2({
                    minimumResultsForSearch: -1,
                });
            $('select[name="employee_id"]').select2({});
            $('#applicable_date').flatpickr({
                plugins: [
                    monthSelectPlugin({
                        shorthand: true,
                        dateFormat: "Y-m",
                        altFormat: "F Y"
                    })
                ]
            });

            $('#apply_once').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).val(1);
                } else {
                    $(this).val(0);
                }
            });

            $('select[name="type"]').on('change', function() {
                let type = $(this).val();
                adjustmentType_type = type;
                ajaxRequest("{{ route('adjustment_types.get-types') }}", "GET", {
                    type: type
                }, false, false, false).done(function(response) {
                    var addNewOption = $('select[name="adjustment_type"] option[value="add_new"]');
                    let selectedOption;
                    $('select[name="adjustment_type"] option').not(addNewOption).remove();

                    response.data.forEach(function(item) {
                        let optionText = lang === "ar" ? item.name : (item.name_en || item
                            .name);
                        $('select[name="adjustment_type"]').append(new Option(optionText,
                            item.id));
                        selectedOption = item.id;
                    });

                    if (addNewOption.length) {
                        $('select[name="adjustment_type"]').append(addNewOption);
                    }
                    if (selectedOption) {
                        $('select[name="adjustment_type"]').val(selectedOption).trigger('change');
                    } else {
                        $('select[name="adjustment_type"]').val(null).trigger('change');
                    }
                    $('select[name="adjustment_type"]').removeAttr('disabled');
                    $('select[name="adjustment_type"]').trigger('change');
                })
            });
            newAdjustmentType(lang, "{{ route('adjustment_types.store') }}");

            $('[name="adjustment_status"], [name="adjustment_deleted_records"], [name="adjustment_type_status"], [name="adjustment_type_deleted_records"]').select2({
                minimumResultsForSearch: -1
            });

            handleFormFiltersDatatable({
                submitButtonClass: '.adjustment_submit_button',
                resetButtonClass: '.adjustment_reset_button',
                deleted_filter: "adjustment_deleted_records",
                dataUrl: '{{ route('adjustments.index') }}',
                dataTable: adjustmentDataTable
            });

            handleFormFiltersDatatable({
                submitButtonClass: '.adjustment_type_submit_button',
                resetButtonClass: '.adjustment_type_reset_button',
                deleted_filter: "adjustment_type_deleted_records",
                dataUrl: '{{ route('adjustment_types.index') }}',
                dataTable: adjustmentTypeDataTable,
            });

        });

        function newAdjustmentType(lang, addAllowanceTypeUrl) {
            const customOptions = new Map();
            initializeSelect2($('select[name="adjustment_type"]'), customOptions, false, lang,
                addAllowanceTypeUrl);
            $(this).off('shown.bs.modal');
        }

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl =
                `{{ url('/adjustment/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    '{{ __('employee::general.this_element') }}'),
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

        $(document).on('click', '.adjustment-type-delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl =
                `{{ url('/adjustment-type/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    '{{ __('employee::general.this_element') }}'),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        adjustmentTypeDataTable.ajax.reload();
                    });
                }
            });
        });

        function handleFormFiltersDatatable(config) {
            const filters = $(config.submitButtonClass);
            const resetButton = $(config.resetButtonClass);
            const deleted = $(`[data-kt-filter="${config.deleted_filter}"]`);
            const dataUrl = config.dataUrl;
            const dataTable = config.dataTable;

            filters.on('click', function(e) {
                const deletedValue = deleted.val();
                
                dataTable.ajax.url(dataUrl + '?' + $.param({
                    deleted_records: deletedValue
                })).load();
            });

            resetButton.on('click', function(e) {
                deleted.val(null).trigger('change');
                dataTable.search('').columns().search('').ajax.url(dataUrl).load();
            });
        }
    </script>
@endsection
