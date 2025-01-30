<div class="tab-pane fade" id="playlists_tab" role="tabpanel">
    <x-cards.card class="shadow-sm pb-5 px-5">
        <div class="p-5">
            <a href="#" id="add_playlist_button"
                class="btn btn-primary fv-row flex-md-root min-w-150px mw-250px">@lang('screen::general.create_playlist')
            </a>
            <table id="playlist_table">
                <thead>
                    <tr class="not-hover"></tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </x-cards.card>

</div>


<script>
    function playlistTab() {
        $('#add_playlist_button').on('click', function(e) {
            e.preventDefault();
            $('#add_playlist_modal').modal('show');
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
