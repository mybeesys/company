@extends('establishment::layouts.master')

@section('title', __('menuItemLang.devices'))
@section('content')
<div class="d-flex flex-column flex-row-fluid gap-5">
    <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
        <li class="nav-item">
            <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                href="#establishments_table_tab">@lang('establishment::general.devices')</a>
        </li>
    </ul>
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="establishments_table_tab" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3></h3>
                <button id="addDeviceBtn" class="btn btn-primary">@lang('establishment::general.add_device')</button>
            </div>
            <x-establishment::devices.table :columns=$columns />
        </div>
    </div>
</div>
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
<script type="text/javascript" src="/vfs_fonts.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/themes/default/style.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.2.1/jstree.min.js"></script>


<script>
    "use strict";
    const table = $('#kt_establishment_table');
    const dataUrl = "{{ url('/devices') }}";
    let dataTable;
    $(document).ready(function() {
        initDatatable();
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
                    success: function(response) {
                        $('#addDeviceModal').modal('hide');
                        $('#deviceName').val('');
                        $('#deviceType').val('');
                        $('#deviceRef').val('');
                        $('#newEstablishment').val('');
                        dataTable.ajax.reload();
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                    }
                });
            }
        });
    });
    $('#generateRefBtn').on('click', function() {
        const generatedRef = 'REF-' + Math.random().toString(36).substr(2, 3);
        $('#deviceRef').val(generatedRef);
        $('#deviceRefError').hide();
    });
    $(document).ready(function() {
        $.ajax({
            url: '/establishment',
            method: 'GET',
            success: function(data) {
                data.forEach(function(item) {
                    $('#newEstablishment').append(
                        $('<option>', {
                            value: item.id,
                            text: item.name
                        })
                    );
                });
            },
            error: function(xhr) {
                console.error('Error', xhr);
            }
        });
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
                        dataTable.ajax.reload();
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

    function initDatatable() {

        dataTable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: dataUrl,
                type: 'GET',
                dataType: 'json',
            },
            info: false,
            columns: [{
                    data: 'id',
                    name: 'id',
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
            ],
            order: [],
            scrollX: true,
            pageLength: 10,
            drawCallback: function() {
                KTMenu.createInstances();
            }
        });

    };
</script>
@endsection