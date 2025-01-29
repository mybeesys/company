<div class="tab-pane fade show active" id="promos_tab" role="tabpanel">
    <x-cards.card class="shadow-sm pb-5 px-5">
        <div class="p-5">
            <a href="#" id="add_promo_button"
                class="btn btn-primary fv-row flex-md-root min-w-150px mw-250px">@lang('screen::general.add_promo')
            </a>
            <table id="promo_table">
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
    function promoTab() {
        $('#add_promo_button').on('click', function() {
            $('#add_promo_modal').modal('toggle');
        });

        $(document).on('click', '.promo-rename-btn', function(e) {
            e.preventDefault();
            $('#rename_promo_modal').modal('toggle');
            let name = $(this).data('name');
            let id = $(this).data('id');
            $('#rename_promo_modal input[name="name"]').val(name);
            $('#rename_promo_modal input[name="id"]').val(id);
        });

        $(document).on('click', '.promo-preview-btn', function(e) {
            e.preventDefault();

            const type = $(this).data('type');
            const path = $(this).data('path');

            $('#preview_image, #preview_video').addClass('d-none');

            if (type.startsWith('image/')) {
                // Show image
                $('#preview_image')
                    .removeClass('d-none')
                    .attr('src', path);
            } else if (type.startsWith('video/')) {
                $('#preview_video')
                    .removeClass('d-none')
                    .find('source')
                    .attr('src', path);

                // Reset and load the video
                const videoElement = $('#preview_video')[0];
                videoElement.load();
            }

            $('#preview_promo_modal').modal('show');
        });

        $('#preview_promo_modal').on('hidden.bs.modal', function() {
            $(this).attr('aria-hidden', false);

            $('#preview_image').attr('src', '');
            $('#preview_video').find('source').attr('src', '');
            const videoElement = $('#preview_video')[0];
            videoElement.pause();
        });

        $(document).on('click', '.promo-delete-btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            let deleteUrl = `{{ url('/promo/${id}') }}`;

            showAlert(`{{ __('employee::general.delete_confirm', ['name' => ':name']) }}`.replace(':name',
                    "{{ __('employee::general.this_element') }}"),
                "{{ __('employee::general.delete') }}",
                "{{ __('employee::general.cancel') }}", undefined,
                true, "warning").then(function(t) {
                if (t.isConfirmed) {
                    ajaxRequest(deleteUrl, 'DELETE').done(function() {
                        promoDataTable.ajax.reload();
                    });
                }
            });
        });
    }

    function initPromoDataTable() {
        promoDataTable = $(promoTable).DataTable({
            processing: true,
            serverSide: true,
            ajax: promoDataUrl,
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
                // loadThumbnail(promoTable);
            },
            rowCallback: function(row, data, index) {
                $(row).addClass('not-hover');
            }
        });
    };

</script>
