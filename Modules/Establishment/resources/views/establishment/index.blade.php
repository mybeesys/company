@extends('establishment::layouts.master')

@section('title', __('menuItemLang.establishments'))

@section('content')
<div class="d-flex flex-column flex-row-fluid gap-5">
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
        <li class="nav-item">
            <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                href="#establishments_table_tab">@lang('establishment::general.establishments_table')</a>
        </li>
        <li class="nav-item">
            <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                href="#establishments_tree_tab">@lang('establishment::general.establishments_tree')</a>
        </li>
        <li class="nav-item">
            <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                href="#devices_table_tab">@lang('establishment::general.devices')</a>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="establishments_table_tab" role="tabpanel">
            <x-establishment::establishments.table :columns=$columns />
        </div>
        <div class="tab-pane fade" id="establishments_tree_tab" role="tabpanel">
            <div class="px-20 d-flex justify-content-end gap-2">
                <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="expand_all">@lang('accounting::lang.expand_all')</button>
                <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="collapse_all">@lang('accounting::lang.collapse_all')</button>
            </div>
            <div id="est_tree">
                <ul>
                    @foreach ($establishments as $establishment)
                    <x-establishment::establishments.tree :establishment=$establishment :name="get_name_by_lang()" />
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="tab-pane fade" id="devices_table_tab" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>@lang('establishment::general.devices')</h3>
                <button id="addDeviceBtn" class="btn btn-primary">@lang('establishment::general.add_device')</button>
            </div>
            <div id="devicesTableContent">
                <x-establishment::devices.table :columns=$deviceColumns />
            </div>
        </div>
    </div>
</div>

<!-- Add Device Modal -->
<div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDeviceModalLabel">@lang('establishment::general.add_device')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addDeviceForm">
                    <div class="mb-3">
                        <label for="deviceName" class="form-label">@lang('establishment::general.device_name')<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="deviceName" required>
                    </div>
                    <div class="mb-3">
                        <label for="deviceType" class="form-label">@lang('establishment::general.device_type')<span class="text-danger">*</span></label>
                        <select class="form-select" id="deviceType" required>
                            <option value="" disabled selected>@lang('establishment::general.select_device_type')</option>
                            <option value="kitchen screen">@lang('establishment::general.kitchen_screen')</option>
                            <option value="cashier">@lang('establishment::general.cashier')</option>
                            <option value="waiters">@lang('establishment::general.waiters')</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="deviceRef" class="form-label">@lang('establishment::fields.ref')<span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="deviceRef" required>
                            <button type="button" id="generateRefBtn" class="btn btn-secondary">@lang('establishment::fields.generate')</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newEstablishment" class="form-label">@lang('establishment::general.establishment')<span class="text-danger">*</span></label>
                        <select class="form-select" id="newEstablishment" required>
                            <option value="" disabled selected>@lang('establishment::general.select_establishment')</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('establishment::general.close')</button>
                <button type="submit" class="btn btn-primary" id="saveDeviceBtn">@lang('establishment::general.save')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
@parent
<script src="{{ url('/js/table.js') }}"></script>
<script src="/vfs_fonts.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>

<script>
    "use strict";
    let dataTableEstablishments, dataTableDevices;

    $(document).ready(function() {
        // Initialize both DataTables
        initDatatable('establishments');
        initDatatable('devices');
        handleFormFiltersDatatable();
        $('[name="status"], [name="deleted_records"]').select2({
            minimumResultsForSearch: -1
        });

        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            var name = $(this).data('name');
            let deleteUrl = $(this).data('deleted') ?
                `{{ url('/establishment/force-delete/${id}') }}` :
                `{{ url('/establishment/${id}') }}`;

            showAlert(`{{ __('establishment::general.delete_confirm', ['name' => ':name']) }}`.replace(
                    ':name',
                    name),
                "{{ __('establishment::general.delete') }}",
                "{{ __('establishment::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        dataTableEstablishments.ajax.reload();
                    });
                }
            });
        });

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr('href');
            if (target === '#devices_table_tab') {
                dataTableDevices.ajax.reload();
            } else {
                dataTableEstablishments.ajax.reload();
            }
        });
        $('#est_tree').jstree({

            "core": {

                "themes": {

                    "responsive": true,

                    "variant": "large"

                }

            },

            "types": {

                "default": {
                    "icon": "fa fa-folder"
                },

                "file": {
                    "icon": "fa fa-file"
                }

            },

            "plugins": ["types", "search"]

        });
        // Add Device Modal
        $('#addDeviceBtn').on('click', function() {
            $('#addDeviceModal').modal('show');
        });

        $('#saveDeviceBtn').on('click', function() {
            const deviceName = $('#deviceName').val();
            const deviceType = $('#deviceType').val();
            const deviceRef = $('#deviceRef').val();
            const newEstablishment = $('#newEstablishment').val();
            let valid = true;

            if (!deviceName) {
                $('#deviceName').addClass('is-invalid');
                valid = false;
            } else {
                $('#deviceName').removeClass('is-invalid');
            }

            if (!deviceType) {
                $('#deviceType').addClass('is-invalid');
                $('#deviceTypeError').show();
                valid = false;
            } else {
                $('#deviceType').removeClass('is-invalid');
                $('#deviceTypeError').hide();
            }

            if (!deviceRef) {
                $('#deviceRef').addClass('is-invalid');
                valid = false;
            } else {
                $('#deviceRef').removeClass('is-invalid');
            }
            if (!newEstablishment) {
                $('#newEstablishment').addClass('is-invalid');
                valid = false;
            } else {
                $('#newEstablishment').removeClass('is-invalid');
            }

            if (valid) {
                $.ajax({
                    url: '/devices/store',
                    method: 'POST',
                    data: {
                        name: deviceName,
                        type: deviceType,
                        ref: deviceRef,
                        establishment_id: newEstablishment,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function() {
                        $('#addDeviceModal').modal('hide');
                        $('#addDeviceForm')[0].reset();
                        dataTableDevices.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                    }
                });
            }
        });

        // Generate Reference
        $('#generateRefBtn').on('click', function() {
            const generatedRef = 'REF-' + Math.random().toString(36).substr(2, 3);
            $('#deviceRef').val(generatedRef);
        });

        // Fetch Establishments for Device Dropdown
        $.ajax({
            url: '/devices/establishment',
            method: 'GET',
            success: function(data) {
                data.forEach(function(item) {
                    $('#newEstablishment').append($('<option>', {
                        value: item.id,
                        text: item.name
                    }));
                });
            },
            error: function(xhr) {
                console.error('Error', xhr);
            }
        });

        // Initialize DataTable
        function initDatatable(type) {
            const url = "{{ url('establishment') }}?" + $.param({
                type: type
            });
            const tableId = type === 'devices' ? '#kt_devices_table' : '#kt_establishment_table';

            if ($.fn.dataTable.isDataTable(tableId)) {
                $(tableId).DataTable().destroy();
            }

            const dataTable = $(tableId).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: url,
                    type: 'GET',
                    dataType: 'json'
                },
                columns: type === 'devices' ? [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'device_name',
                        name: 'device_name'
                    },
                    {
                        data: 'device_type',
                        name: 'device_type'
                    },
                    {
                        data: 'ref',
                        name: 'ref'
                    },
                    {
                        data: 'establishment',
                        name: 'establishment'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ] : [{
                        data: 'id',
                        name: 'id'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'is_main',
                        name: 'is_main'
                    },
                    {
                        data: 'parent_id',
                        name: 'parent_id'
                    },
                    {
                        data: 'contact_details',
                        name: 'contact_details'
                    },
                    {
                        data: 'is_active',
                        name: 'is_active'
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
                info: false,
                drawCallback: function() {
                    KTMenu.createInstances();
                }
            });

            if (type === 'devices') {
                dataTableDevices = dataTable;
            } else {
                dataTableEstablishments = dataTable;
            }
        }
    });

    function deleteDevice(id) {
        Swal.fire({
            title: "{{ __('establishment::general.are_you_sure')}}",
            text: "{{ __('establishment::general.you_will_delete_this_device')}}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: "{{ __('establishment::general.yes')}}",
            cancelButtonText: "{{ __('establishment::general.no')}}",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/devices/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            "{{ __('establishment::general.deleted')}}",
                            "{{ __('establishment::general.successfully_deleted')}}",
                            'success'
                        );
                        dataTableDevices.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        Swal.fire(
                            'error!',
                            'error',
                            'error'
                        );
                    }
                });
            }
        });
    }
    $(document).on('click', '#expand_all', function(e) {

        $('#est_tree').jstree("open_all");

    })

    $(document).on('click', '#collapse_all', function(e) {

        $('#est_tree').jstree("close_all");

    });
    $(document).on('click', '.restore-btn', function(e) {

        var id = $(this).data('id');

        ajaxRequest(`{{ url('/establishment/restore/${id}') }}`, 'POST').done(function() {

            dataTableEstablishments.ajax.reload();

        });

    })

    function handleFormFiltersDatatable() {

        const filters = $('[data-kt-filter="filter"]');

        const resetButton = $('[data-kt-filter="reset"]');

        const status = $('[data-kt-filter="status"]');

        const deleted = $('[data-kt-filter="deleted_records"]');

        filters.on('click', function(e) {

            const deletedValue = deleted.val();

            dataTableEstablishments.ajax.url("{{ url('establishment') }}?" + $.param({

                deleted_records: deletedValue

            })).load();

            const statusValue = status.val();

            dataTableEstablishments.column(4).search(statusValue).draw();

        });

        resetButton.on('click', function(e) {

            status.val(null).trigger('change');

            deleted.val(null).trigger('change');

            dataTableEstablishments.search('').columns().search('').ajax.url(dataUrl)

                .load();

        });

    };
</script>
@endsection