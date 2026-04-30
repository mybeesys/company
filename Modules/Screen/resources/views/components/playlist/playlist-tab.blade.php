<div class="tab-pane fade screen-tab-pane" id="playlists_tab" role="tabpanel">
    <div class="screen-tab-header">
        <div>
            <h2>@lang('screen::general.tab_playlists_title')</h2>
            <p class="screen-tab-desc">@lang('screen::general.tab_playlists_desc')</p>
        </div>
        <a href="#" id="add_playlist_button" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>@lang('screen::general.create_playlist')
        </a>
    </div>
    <div class="screen-table-card">
        <div class="table-responsive rounded-3 bg-white">
            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0" id="playlist_table">
                <thead>
                    <tr class="not-hover"></tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>


<script>
    function playlistTab() {
        $('#add_playlist_button').on('click', function(e) {
            e.preventDefault();
            $('#add_playlist_modal_form [name="playlist_id"]').val('');
            $('#add_playlist_modal').modal('show');
        });
        $(document).on('click', '.playlist-edit-btn', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            ajaxRequest(`{{ url('/playlist') }}/${id}`, 'GET', {}, false, false, false).done(function(response) {
                const data = response?.data || {};
                const settings = data.days_settings || {};
                $('#add_playlist_modal_form [name="playlist_id"]').val(data.id || '');
                $('#add_playlist_modal_form [name="name"]').val(data.name || '');
                $('select[name="days_settings"]').val(settings.days_settings_option || 'every_day').trigger('change');
                $('#start_time').val(settings.start_time || '');
                $('#start_date_time').val(settings.start_date_time || '');
                $('select[name="days_of_the_weak"]').val(settings.days_of_the_weak || []).trigger('change');
                $('select[name="screen_orientation"]').val(settings.screen_orientation || 'landscape').trigger('change');
                $('select[name="establishments_ids"]').val(data.establishments_ids || []).trigger('change');
                $('#add_playlist_modal_form [name="transition_seconds"]').val(settings.transition_seconds || 5);
                syncDevicesByEstablishments(data.devices || []);

                selectedInOrder = (data.selected_promos || []).map(String);
                if (promoPlaylistDataTable) {
                    promoPlaylistDataTable.rows().deselect();
                    promoPlaylistDataTable.rows().every(function() {
                        const rowData = this.data();
                        if (selectedInOrder.includes(String(rowData.DT_RowId))) {
                            this.select();
                        }
                    });
                }
                $('#add_playlist_modal').modal('show');
            });
        });

        $(document).on('click', '.playlist-delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl = `{{ url('/playlist/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    "{{ __('employee::general.this_element') }}"),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        playlistDataTable.ajax.reload();
                    });
                }
            });
        });
    }

    function initPlaylistDataTable() {
        playlistDataTable = $(playlistTable).DataTable({
            processing: true,
            serverSide: true,
            ajax: playlistDataUrl,
            info: false,
            language: {
                emptyTable: "{{ app()->getLocale() === 'ar' ? 'لا توجد قوائم تشغيل بعد' : 'No playlists yet' }}"
            },
            columns: [{
                data: 'main',
                name: 'main',
                orderable: false
            }, ],
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
    };
</script>
