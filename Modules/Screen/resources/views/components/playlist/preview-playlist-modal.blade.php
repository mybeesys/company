<x-general.modal module="screen" id='preview_playlist_modal' title='preview_playlist' class='mw-900px' :submitButton="false"
    :form="false">
    <div class="stepper stepper-pills" id="preview_playlist_stepper">
        <div class="preview-playlist-stepper-nav flex-center flex-wrap d-none">
        </div>
        <div class="preview-playlist-content mb-5">
        </div>
        <div class="d-flex flex-stack">
            <div class="me-2">
                <button type="button" class="btn btn-light btn-active-light-primary" data-kt-stepper-action="previous">
                    @lang('screen::general.back')
                </button>
            </div>
            <div>
                <button type="button" class="btn btn-primary" data-kt-stepper-action="next">
                    @lang('screen::general.next')
                </button>
            </div>
        </div>
    </div>
</x-general.modal>


<script>
    function previewPlaylistModal() {
        // Update the click handler to handle the media display
        $(document).on('click', '.playlist-preview-btn', function(e) {
            e.preventDefault();
            let id = $(this).data('id');
            let currentStep = 0;

            ajaxRequest(`{{ url('/playlist/get-promos/${id}') }}`, "GET", {}, false).done(function(response) {
                // Reset and clear previous content
                $('.preview-playlist-stepper-nav').empty();
                $('.preview-playlist-content').empty();

                // Create stepper navigation and content dynamically
                let navHtml = '';
                let contentHtml = '';
                let tenantPath = '{{ asset('storage/tenant' . tenancy()->tenant->id . '/') }}';

                const totalSteps = response.data.length;

                response.data.forEach((item, index) => {
                    // Add stepper nav item
                    navHtml +=
                        `<div class="stepper-item mx-8 my-4 ${index === 0 ? 'current' : ''}" data-kt-stepper-element="nav"></div>`;

                    // Add content section
                    let mediaContent = '';
                    if (item.path.toLowerCase().endsWith('.mp4')) {
                        mediaContent = `
                    <video class="w-100" controls>
                        <source src="${tenantPath}/${item.path}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>`;
                    } else if (item.path.toLowerCase().match(/\.(jpg|jpeg|png|gif)$/)) {
                        mediaContent =
                            `<img src="${tenantPath}/${item.path}" class="w-100" alt="Media content">`;
                    }

                    contentHtml += `
                <div class="flex-column preview-playlist-stepper-content ${index === 0 ? 'current' : 'pending'}" data-kt-stepper-element="content">
                    ${mediaContent}
                </div>`;
                });

                // Add the generated HTML to the stepper
                $('.preview-playlist-stepper-nav').html(navHtml);
                $('.preview-playlist-content').html(contentHtml);

                // Show/hide navigation buttons based on media count
                if (totalSteps <= 1) {
                    $('[data-kt-stepper-action="previous"]').hide();
                    $('[data-kt-stepper-action="next"]').hide();
                } else {
                    $('[data-kt-stepper-action="previous"]').show();
                    $('[data-kt-stepper-action="next"]').show();
                }

                // Handle next button click
                $('[data-kt-stepper-action="next"]').off('click').on('click', function() {
                    if (currentStep < totalSteps - 1) {
                        // Hide current step
                        $('.preview-playlist-stepper-nav .stepper-item').eq(currentStep)
                            .removeClass('current');
                        $('.preview-playlist-stepper-content').eq(currentStep).removeClass(
                            'current').addClass('pending');

                        // Show next step
                        currentStep++;
                        $('.preview-playlist-stepper-nav .stepper-item').eq(currentStep)
                            .addClass('current');
                        $('.preview-playlist-stepper-content').eq(currentStep).removeClass(
                            'pending').addClass('current');
                    }
                });

                // Handle previous button click
                $('[data-kt-stepper-action="previous"]').off('click').on('click', function() {
                    if (currentStep > 0) {
                        // Hide current step
                        $('.preview-playlist-stepper-nav .stepper-item').eq(currentStep)
                            .removeClass('current');
                        $('.preview-playlist-stepper-content').eq(currentStep).removeClass(
                            'current').addClass('pending');

                        // Show previous step
                        currentStep--;
                        $('.preview-playlist-stepper-nav .stepper-item').eq(currentStep)
                            .addClass('current');
                        $('.preview-playlist-stepper-content').eq(currentStep).removeClass(
                            'pending').addClass('current');
                    }
                });

                // Handle modal cleanup
                $('#preview_playlist_modal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                    $('.preview-playlist-stepper-nav').empty();
                    $('.preview-playlist-content').empty();
                    currentStep = 0;
                });

                $('#preview_playlist_modal').modal('show');
            });
        });
    }
</script>
