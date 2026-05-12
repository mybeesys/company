<div class="tab-pane fade screen-tab-pane" id="devices_tab" role="tabpanel">
    <div class="screen-tab-header">
        <div>
            <h2>@lang('screen::general.tab_devices_title')</h2>
            <p class="screen-tab-desc">@lang('screen::general.tab_devices_desc')</p>
        </div>
        <a href="#" id="add_device_button" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>@lang('screen::general.add_device')
        </a>
    </div>
    <div class="screen-table-card">
        <div class="table-responsive rounded-3 bg-white">
            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0 w-100" id="device_table">
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function initDeviceDataTable() {
        deviceDataTable = $(deviceTable).DataTable({
            processing: true,
            serverSide: true,
            ajax: deviceDataUrl,
            info: false,
            autoWidth: false,
            language: {
                emptyTable: "{{ app()->getLocale() === 'ar' ? 'لا توجد أجهزة بعد' : 'No devices yet' }}"
            },
            columns: [{
                    data: 'code',
                    name: 'code',
                    title: @json(__('sales::fields.code')),
                    className: 'text-start align-middle text-break',
                    width: '26%',
                    orderable: false,
                },
                {
                    data: 'establishment_name',
                    name: 'establishment_name',
                    title: @json(__('screen::general.branch')),
                    className: 'text-start align-middle text-break',
                    width: '44%',
                    orderable: false,
                },
                {
                    data: 'actions',
                    name: 'actions',
                    title: @json(__('messages.actions')),
                    className: 'text-start align-middle text-nowrap',
                    width: '30%',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [],
            pageLength: 5,
            drawCallback: function() {
                KTMenu.createInstances();
                this.api().columns.adjust();
            },
            rowCallback: function(row, data, index) {
                $(row).addClass('not-hover');
            }
        });
        $('a[data-bs-toggle="tab"][href="#devices_tab"]').on('shown.bs.tab', function() {
            if (typeof deviceDataTable !== 'undefined' && deviceDataTable) {
                deviceDataTable.columns.adjust();
            }
        });
        $('#add_device_button').on('click', function() {
            if (typeof setDeviceModalTitleMode === 'function') {
                setDeviceModalTitleMode('add');
            }
            $('#add_device_modal_form')[0].reset();
            $('#add_device_modal_form [name="id"]').val('');
            $('.device-clear-pin-wrap, .device-regenerate-wrap').addClass('d-none');
            $('#add_device_modal').modal('toggle');
        });
        $(document).on('click', '.device-edit-btn', function(e) {
            e.preventDefault();
            if (typeof setDeviceModalTitleMode === 'function') {
                setDeviceModalTitleMode('edit');
            }
            $('#add_device_modal_form [name="id"]').val($(this).data('id'));
            $('#add_device_modal_form [name="code"]').val($(this).data('code'));
            $('#add_device_modal_form [name="establishment_id"]').val($(this).data('establishment-id')).trigger('change');
            $('#add_device_modal_form [name="pin"]').val('');
            $('#device_clear_pin').prop('checked', false);
            $('.device-clear-pin-wrap, .device-regenerate-wrap').removeClass('d-none');
            $('#add_device_modal').modal('show');
        });
        $(document).on('click', '.device-delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl = `{{ url('/device') }}/${id}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    "{{ __('employee::general.this_element') }}"),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        deviceDataTable.ajax.reload();

                        let selectElement = $('select[name="devices"]');
                        selectElement.find(`option[value='${id}']`).remove();
                        selectElement.trigger('change');
                    }).fail(function(xhr) {
                        let message = xhr?.responseJSON?.error || "{{ __('employee::responses.something_wrong_happened') }}";
                        toastr.error(message);
                    });
                }
            });
        });
    }
</script>
