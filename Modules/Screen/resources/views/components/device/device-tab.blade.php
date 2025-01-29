<div class="tab-pane fade" id="devices_tab" role="tabpanel">
    <x-cards.card class="shadow-sm pb-5 px-5">
        <div class="p-5">
            <a href="#" id="add_device_button"
                class="btn btn-primary fv-row flex-md-root min-w-150px mw-250px">@lang('screen::general.add_device')
            </a>
            <div class="table-responsive">
                <table class="table align-middle table-striped table-row-bordered fs-6 gy-5" id="device_table">
                    <thead>
                        <tr class="not-hover">
                            <th class="text-start">@lang('sales::fields.code')</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </x-cards.card>
</div>

<script>
    function initDeviceDataTable() {
        deviceDataTable = $(deviceTable).DataTable({
            processing: true,
            serverSide: true,
            ajax: deviceDataUrl,
            info: false,
            columns: [{
                    data: 'code',
                    name: 'code',
                    className: 'px-5',
                    orderable: false,
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
            pageLength: 5,
            drawCallback: function() {
                KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
            },
            rowCallback: function(row, data, index) {
                $(row).addClass('not-hover');
            }
        });
        $('#add_device_button').on('click', function() {
            $('#add_device_modal').modal('toggle');
        });
        $(document).on('click', '.device-delete-btn', function(e){
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl = `{{ url('/device/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    "{{ __('employee::general.this_element') }}"),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        deviceDataTable.ajax.reload();
                    });
                }
            });
        });
    }
</script>
