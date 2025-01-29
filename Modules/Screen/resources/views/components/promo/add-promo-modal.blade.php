<x-general.modal module="screen" id='add_promo_modal' title='add_promo' class='mw-800px'>
    <div class="upload-container">
        <div class="fv-row">
            <div class="dropzone">
                <input type="file" id="promo_upload" name="promo" style="display: none;" />
                <div class="dz-message needsclick" onclick="$('#promo_upload').click()">
                    <i class="ki-duotone ki-file-up fs-3x text-primary"><span class="path1"></span><span
                            class="path2"></span></i>
                    <div class="ms-4">
                        <h3 class="text-start fs-5 fw-bold text-gray-900 mb-1">@lang('screen::general.click_to_upload')</h3>
                        <span class="fs-7 fw-semibold text-gray-500">@lang('screen::general.promo_hint')</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <div class="upload-size-info"></div>
            <div class="upload-progress mt-1" style="display: none;">
                <div class="upload-progress-bar" role="upload-progress-bar" style="width: 0%" aria-valuenow="0"
                    aria-valuemin="0" aria-valuemax="100">0%</div>
                <div class="upload-info text-muted"></div>
            </div>
        </div>
    </div>
</x-general.modal>

<script>
    function addPromoModal() {
        $('#add_promo_modal_form').on('submit', function(e) {
            e.preventDefault();
            const form = $(this)[0];
            let data = new FormData(form);


            const fileInput = $('#promo_upload')[0];
            if (!fileInput.files.length) {
                alert('Please select a file to upload.');
                return;
            }

            const $uploadProgress = $('.upload-progress');
            const $uploadProgressBar = $('.upload-progress-bar');
            const $uploadInfo = $uploadProgress.find('.upload-info');
            const submitButton = $(this).find('button[type="submit"]');

            submitButton.prop('disabled', true);
            request = ajaxRequest("{{ route('promos.store') }}", 'POST', data, true, true, true, {
                uploadProgress: function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        const loadedSize = formatBytes(e.loaded);
                        const totalSize = formatBytes(e.total);

                        $uploadProgressBar
                            .css('width', percentComplete + '%')
                            .attr('aria-valuenow', Math.ceil(percentComplete))
                            .text(Math.ceil(percentComplete) + '%');

                        if ($uploadInfo.length) {
                            $('.upload-size-info').text(
                                `{{ __('screen::general.uploading') }}: ${loadedSize} {{ __('screen::general.of') }} ${totalSize}`
                            )
                            $uploadInfo.text('');
                        }
                    }
                },
                complete: function() {
                    submitButton.prop('disabled', false);
                    $('#add_promo_modal').modal('toggle');

                    promoDataTable.ajax.reload();

                    setTimeout(() => {
                        $uploadProgress.hide();
                        $uploadProgressBar
                            .css('width', '0%')
                            .text('0%');
                        $uploadInfo.remove();
                        $('.upload-size-info').text('');
                    }, 1000);
                }
            }).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' +
                            value +
                            '</div>');
                    });
                });
        });

        $('#add_promo_modal_form').on('reset', function() {
            request.abort();
        });
        $('#promo_upload').on('change', function() {
            const file = this.files[0];
            const allowedTypes = ['image/jpeg', 'image/png',
                'video/mp4'
            ];
            const maxSize = 120 * 1024 * 1024;

            if (file) {
                const $uploadProgress = $('.upload-progress');
                const $uploadProgressBar = $('.upload-progress-bar');
                const $uploadInfo = $uploadProgress.find('.upload-info');

                // Check file type (MIME type)
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload a JPG, PNG, or MP4 file.');
                    $(this).val('');
                    return;
                }

                // Check file size
                if (file.size > maxSize) {
                    alert('File size exceeds the 120MB limit.');
                    $(this).val('');
                    return;
                }

                $uploadProgress.show();
                $uploadInfo.text(`${file.name} (${formatBytes(file.size)})`)
            }
        });
    }

    function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';

            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];

            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
</script>
