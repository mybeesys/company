<div class="modal fade" id="preview_promo_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-800px">
        <div class="modal-content">
            <div class="modal-header mb-2">
                <h2 class="fw-bold">@lang('screen::general.preview_promo')</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body mx-5">
                <div class="text-center pt-5">
                    <div class="p-4 bg-white rounded">
                        <img id="preview_image" class="w-100 d-none">
                        <video id="preview_video" class="w-100 d-none" controls>
                            <source src="" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                    <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">@lang('general.cancel')</button>
                </div>
            </div>
        </div>
    </div>
</div>
