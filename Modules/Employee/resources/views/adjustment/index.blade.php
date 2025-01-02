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
                        <x-tables.table-header :search="false" model="adjustment" :addButton="auth()->user()->hasDashboardPermission('employees.allowance_deduction.create')" module="employee" />
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
                            module="employee" />
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
    <script src="{{ url('/modules/employee/js/adjustment-type.js') }}"></script>
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
            addAllowanceForm();
            addAllowanceTypeForm();

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

        $(document).on('click', '.adjustment-type-edit-btn', function() {
            var id = $(this).data('id');
            var adjustmentTypeType = $(this).data('adjustmentTypeType');
            var name = $(this).data('name');
            var name_en = $(this).data('nameEn');

            $('#add_adjustment_type_modal').modal('toggle');
            $('#adjustment_type_id').val(id);
            $('.modal-header h2').html("{{ __('employee::general.edit_adjustment') }}");
            $('#amount').val(amount);
            $('select[name="adjustment_type_type"]').val(adjustmentTypeType).trigger('change');
            $('#name').val(name);
            $('#name_en').val(name_en);
        });

        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            var adjustmentType = $(this).data('adjustmentType');
            var employeeId = $(this).data('employeeId');
            var amount = $(this).data('amount');
            var amountType = $(this).data('amountType');
            var applyOnce = $(this).data('applyOnce');
            var applicableDate = $(this).data('applicableDate').substring(0, 7);
            var type = $(this).data('type');

            $('#add_adjustment_modal').modal('toggle');
            $('#id').val(id);
            $('.modal-header h2').html("{{ __('employee::general.edit_adjustment') }}");
            $('#amount').val(amount);
            $('select[name="amount_type"]').val(amountType).trigger('change');
            $('select[name="adjustment_type"]').val(adjustmentType).trigger('change');
            $('select[name="employee_id"]').val(employeeId).trigger('change');
            $('select[name="type"]').val(type).trigger('change');
            $('#applicable_date').val(applicableDate);
            $('#apply_once').val(applyOnce);

            if (applyOnce) {
                $('#apply_once').attr('checked', applyOnce);
            }
        });

        $(document).on('click', '.nav-link-adjustment-type', function() {
            adjustmentTypeDataTable.ajax.reload();
        });

        $(document).on('click', '.nav-link-adjustment', function() {
            adjustmentDataTable.ajax.reload();
        });

        function addAllowanceForm() {
            $('#add_adjustment_modal_form').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serializeArray();
                data.push({ name: "_token", value: window.csrfToken });
                ajaxRequest("{{ route('adjustments.store') }}", "POST", data).fail(
                    function(data) {
                        $.each(data.responseJSON.errors, function(key, value) {
                            $(`[name='${key}']`).addClass('is-invalid');
                            $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                                '</div>');
                        });
                    }).done(function() {
                    $('#add_adjustment_modal').modal('toggle');
                    adjustmentDataTable.ajax.reload();
                });
            });
        }

        function addAllowanceTypeForm() {
            $('#add_adjustment_type_modal_form').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serializeArray();
                data.push({ name: "_token", value: window.csrfToken });
                ajaxRequest("{{ route('adjustment_types.store') }}", "POST", data).fail(
                    function(data) {
                        $.each(data.responseJSON.errors, function(key, value) {
                            $(`[name='${key}']`).addClass('is-invalid');
                            $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                                '</div>');
                        });
                    }).done(function() {
                    $('#add_adjustment_type_modal').modal('toggle');
                    adjustmentTypeDataTable.ajax.reload();
                });
            });
        }

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
