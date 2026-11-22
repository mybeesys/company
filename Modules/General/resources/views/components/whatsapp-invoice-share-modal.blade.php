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
                    <div id="whatsappShareCloudBanner" class="alert alert-success d-none py-3 mb-5">
                        @lang('general::lang.whatsapp_share_cloud_ready')
                    </div>
                    <div id="whatsappShareManualBanner" class="alert alert-warning d-none py-3 mb-5">
                        @lang('general::lang.whatsapp_share_cloud_required')
                    </div>
                    <div class="mb-5">
                        <label class="form-label fw-semibold" for="whatsappSharePhone">@lang('general::lang.whatsapp_share_phone')</label>
                        <input type="text" id="whatsappSharePhone" class="form-control form-control-solid" dir="ltr"
                               placeholder="9665xxxxxxxx" autocomplete="tel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="whatsappShareMessage">@lang('general::lang.whatsapp_share_message')</label>
                        <textarea id="whatsappShareMessage" class="form-control form-control-solid" rows="12" dir="auto"></textarea>
                    </div>
                    <p class="text-muted fs-8 mb-0" id="whatsappShareNote">@lang('general::lang.whatsapp_share_link_note')</p>
                    <input type="hidden" id="whatsappShareDownloadUrl" value="">
                    <input type="hidden" id="whatsappShareTransactionId" value="">
                    <input type="hidden" id="whatsappShareCloudConfigured" value="0">
                </div>
            </div>
            <div class="modal-footer border-0 px-6 pb-6 pt-0 gap-2">
                <button type="button" class="btn btn-light" id="whatsappShareCopyBtn">@lang('general::lang.whatsapp_share_copy')</button>
                <button type="button" class="btn btn-light-primary d-none" id="whatsappShareOpenBtn">
                    <i class="fab fa-whatsapp fs-3 me-1"></i>
                    @lang('general::lang.whatsapp_share_open')
                </button>
                <button type="button" class="btn btn-primary" id="whatsappShareSendBtn" style="background:#25D366;border-color:#25D366;">
                    <i class="fab fa-whatsapp fs-3 me-1"></i>
                    <span id="whatsappShareSendLabel">@lang('general::lang.whatsapp_share_send')</span>
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
    const $txId = $('#whatsappShareTransactionId');
    const $cloud = $('#whatsappShareCloudConfigured');
    const $title = $('#whatsappShareTitle');
    const $subtitle = $('#whatsappShareSubtitle');
    const $note = $('#whatsappShareNote');
    const $sendBtn = $('#whatsappShareSendBtn');
    const $openBtn = $('#whatsappShareOpenBtn');
    const $sendLabel = $('#whatsappShareSendLabel');
    const copiedText = @json(__('general::lang.whatsapp_share_copied'));
    const sendLabel = @json(__('general::lang.whatsapp_share_send'));
    const openLabel = @json(__('general::lang.whatsapp_share_open'));
    const sendingLabel = @json(__('general::lang.whatsapp_share_sending'));
    const cloudNote = @json(__('general::lang.whatsapp_share_cloud_note'));
    const manualNote = @json(__('general::lang.whatsapp_share_link_note'));
    const cloudSubtitle = @json(__('general::lang.whatsapp_share_subtitle_cloud'));
    const manualSubtitle = @json(__('general::lang.whatsapp_share_subtitle'));

    function setBusy(busy) {
        $loading.toggleClass('d-none', !busy);
        $form.toggleClass('d-none', busy);
        $('#whatsappShareOpenBtn, #whatsappShareCopyBtn, #whatsappShareSendBtn').prop('disabled', busy);
    }

    function applyMode(cloudConfigured) {
        const on = !!cloudConfigured;
        $cloud.val(on ? '1' : '0');
        $('#whatsappShareCloudBanner').toggleClass('d-none', !on);
        $('#whatsappShareManualBanner').toggleClass('d-none', on);
        $openBtn.toggleClass('d-none', on);
        $sendLabel.text(on ? sendLabel : openLabel);
        $subtitle.text(on ? cloudSubtitle : manualSubtitle);
        $note.text(on ? cloudNote : manualNote);
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
        $txId.val(String(transactionId || ''));
        applyMode(false);
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
            applyMode(!!data.cloud_configured);
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

    $sendBtn.on('click', function () {
        const cloudOn = $cloud.val() === '1';
        if (!cloudOn) {
            window.open(buildWaUrl($phone.val(), $message.val()), '_blank', 'noopener');
            return;
        }

        const id = $txId.val();
        if (!id) return;

        $sendBtn.prop('disabled', true);
        $sendLabel.text(sendingLabel);

        $.ajax({
            url: @json(url('/transaction-whatsapp-share')) + '/' + id,
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token())
            },
            data: {
                phone: $phone.val(),
                message: $message.val()
            }
        }).done(function (data) {
            if (typeof toastr !== 'undefined') {
                toastr.success(data.message || @json(__('general::lang.whatsapp_cloud_send_success')));
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        }).fail(function (xhr) {
            const msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : @json(__('general::lang.whatsapp_cloud_send_failed'));
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            } else {
                alert(msg);
            }
        }).always(function () {
            $sendBtn.prop('disabled', false);
            $sendLabel.text(sendLabel);
        });
    });

    $openBtn.on('click', function () {
        window.open(buildWaUrl($phone.val(), $message.val()), '_blank', 'noopener');
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
