<div class="modal fade" id="whatsappInvoiceShareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg overflow-hidden">
            <div class="px-6 pt-6 pb-4" style="background: linear-gradient(135deg, #fff8e6 0%, #ffffff 70%); border-bottom: 3px solid #e9b71f;">
                <div class="d-flex align-items-start justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                  style="width:36px;height:36px;background:#25D366;">
                                <i class="fab fa-whatsapp text-white fs-3"></i>
                            </span>
                            <h2 class="fs-3 fw-bold mb-0" id="whatsappShareTitle">@lang('general::lang.whatsapp_share_title')</h2>
                        </div>
                        <p class="text-muted fs-7 mb-0" id="whatsappShareSubtitle">@lang('general::lang.whatsapp_share_subtitle')</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body px-6 py-6">
                <div id="whatsappShareLoading" class="text-center py-10 d-none">
                    <span class="spinner-border text-warning" role="status"></span>
                    <div class="text-muted mt-3">@lang('general::lang.whatsapp_share_loading')</div>
                </div>
                <div id="whatsappShareForm">
                    <div class="mb-5">
                        <label class="form-label fw-semibold" for="whatsappSharePhone">@lang('general::lang.whatsapp_share_phone')</label>
                        <input type="text" id="whatsappSharePhone" class="form-control form-control-solid" dir="ltr"
                               placeholder="9665xxxxxxxx" autocomplete="tel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="whatsappShareMessage">@lang('general::lang.whatsapp_share_message')</label>
                        <textarea id="whatsappShareMessage" class="form-control form-control-solid" rows="12" dir="auto"></textarea>
                    </div>
                    <p class="text-muted fs-8 mb-0">@lang('general::lang.whatsapp_share_link_note')</p>
                    <input type="hidden" id="whatsappShareDownloadUrl" value="">
                </div>
            </div>
            <div class="modal-footer border-0 px-6 pb-6 pt-0 gap-2">
                <button type="button" class="btn btn-light" id="whatsappShareCopyBtn">@lang('general::lang.whatsapp_share_copy')</button>
                <button type="button" class="btn btn-primary" id="whatsappShareOpenBtn" style="background:#25D366;border-color:#25D366;">
                    <i class="fab fa-whatsapp fs-3 me-1"></i>
                    @lang('general::lang.whatsapp_share_open')
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modalEl = document.getElementById('whatsappInvoiceShareModal');
    if (!modalEl || modalEl.dataset.bound === '1') {
        return;
    }
    modalEl.dataset.bound = '1';

    const $modal = $('#whatsappInvoiceShareModal');
    const $loading = $('#whatsappShareLoading');
    const $form = $('#whatsappShareForm');
    const $phone = $('#whatsappSharePhone');
    const $message = $('#whatsappShareMessage');
    const $download = $('#whatsappShareDownloadUrl');
    const $title = $('#whatsappShareTitle');
    const copiedText = @json(__('general::lang.whatsapp_share_copied'));

    function setBusy(busy) {
        $loading.toggleClass('d-none', !busy);
        $form.toggleClass('d-none', busy);
        $('#whatsappShareOpenBtn, #whatsappShareCopyBtn').prop('disabled', busy);
    }

    function buildWaUrl(phone, message) {
        const digits = String(phone || '').replace(/\D+/g, '');
        const query = 'text=' + encodeURIComponent(message || '');
        return digits ? ('https://wa.me/' + digits + '?' + query) : ('https://wa.me/?' + query);
    }

    function openShare(transactionId) {
        setBusy(true);
        $phone.val('');
        $message.val('');
        $download.val('');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        $.ajax({
            url: @json(url('/transaction-whatsapp-share')) + '/' + transactionId,
            method: 'GET',
            headers: { 'Accept': 'application/json' }
        }).done(function (data) {
            $title.text(@json(__('general::lang.whatsapp_share_title')) + (data.ref_no ? (' — ' + data.ref_no) : ''));
            $phone.val(data.phone_display || data.phone || '');
            $message.val(data.message || '');
            $download.val(data.download_url || '');
            setBusy(false);
        }).fail(function (xhr) {
            setBusy(false);
            modal.hide();
            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : @json(__('general::lang.whatsapp_share_unsupported'));
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            } else {
                alert(msg);
            }
        });
    }

    $(document).on('click', '.whatsapp-invoice-share', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        if (!id) return;
        openShare(id);
    });

    $('#whatsappShareOpenBtn').on('click', function () {
        const url = buildWaUrl($phone.val(), $message.val());
        window.open(url, '_blank', 'noopener');
    });

    $('#whatsappShareCopyBtn').on('click', function () {
        const text = $message.val() || '';
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                if (typeof toastr !== 'undefined') toastr.success(copiedText);
            });
        } else {
            $message.trigger('select');
            document.execCommand('copy');
            if (typeof toastr !== 'undefined') toastr.success(copiedText);
        }
    });
})();
</script>
