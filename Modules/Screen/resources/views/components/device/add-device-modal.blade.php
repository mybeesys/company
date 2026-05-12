@props(['establishments'])
<x-general.modal module="screen" id="add_device_modal" :title="null"
    class="modal-lg mw-lg-650px device-add-modal-dialog" body_class="device-add-modal-body pt-0 pb-2">
    <x-slot:header>
        <h2 class="fw-bold mb-0 text-gray-900" id="device_modal_title_text">@lang('screen::general.add_device')</h2>
    </x-slot:header>

    <style>
        .device-add-modal-dialog {
            max-width: min(96vw, 640px);
        }

        #add_device_modal .device-add-modal-body {
            margin-left: 1rem !important;
            margin-right: 1rem !important;
            max-height: min(78vh, 720px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        @media (min-width: 768px) {
            #add_device_modal .device-add-modal-body {
                margin-left: 1.5rem !important;
                margin-right: 1.5rem !important;
            }
        }

        #add_device_modal .device-form-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #7e8299;
            margin-bottom: 0.65rem;
        }

        #add_device_modal .device-form-card {
            border: 1px solid #eff2f5;
            border-radius: 12px;
            padding: 1rem 1.1rem;
            background: #fcfcfd;
            margin-bottom: 1rem;
        }

        .swal2-popup.device-pairing-swal {
            max-width: min(92vw, 400px) !important;
            width: min(92vw, 400px) !important;
            padding: 1rem 1rem 1.1rem !important;
        }

        .swal2-popup.device-pairing-swal .swal2-title {
            font-size: 1.05rem !important;
            padding: 0 0 0.35rem !important;
        }

        .swal2-html-container.device-pairing-swal-html {
            overflow: visible !important;
            max-height: none !important;
            padding: 0.35rem 0 0 !important;
        }

        .device-pairing-qr-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0.5rem;
            background: #fff;
            border: 1px solid #e4e6ef;
            border-radius: 10px;
            margin-bottom: 0.75rem;
        }

        .device-pairing-qr-wrap svg {
            width: min(72vw, 240px) !important;
            height: auto !important;
            max-width: 100%;
            display: block;
        }

        .device-pairing-token-box {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.7rem;
            line-height: 1.45;
            word-break: break-all;
            background: #f5f8fa;
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            padding: 0.5rem 0.6rem;
            max-height: 96px;
            overflow-y: auto;
        }
    </style>

    <div class="device-modal-hint d-flex align-items-start gap-3 mb-4">
        <span class="text-primary pt-1 flex-shrink-0"><i class="fas fa-circle-info"></i></span>
        <span class="text-gray-700 fs-7 lh-lg">@lang('screen::general.device_modal_hint')</span>
    </div>

    <div class="device-form-section-title">@lang('screen::general.device_form_section_main')</div>
    <div class="device-form-card">
        <div class="row g-4">
            <div class="col-12">
                <input type="hidden" name="id" value="">
                <x-form.input-div class="mb-0">
                    <x-form.input required :errors=$errors
                        placeholder="{{ __('screen::fields.device_code') }}"
                        value=""
                        name="code"
                        :label="__('screen::fields.device_code')" />
                </x-form.input-div>
            </div>
            <div class="col-12">
                <x-form.input-div class="mb-0">
                    <x-form.select name="establishment_id" :label="__('screen::general.branch')" :options="$establishments"
                        :errors="$errors" data_allow_clear="false" required></x-form.select>
                </x-form.input-div>
            </div>
        </div>
    </div>

    <div class="device-form-section-title">@lang('screen::general.device_form_section_access')</div>
    <div class="device-form-card">
        <div class="row g-4">
            <div class="col-12">
                <x-form.input-div class="mb-0">
                    <label class="form-label fw-semibold">{{ __('screen::general.device_pin_label') }}</label>
                    <input type="password" name="pin" class="form-control form-control-solid" autocomplete="new-password"
                        maxlength="32" placeholder="{{ __('screen::general.device_pin_placeholder') }}">
                    <div class="form-text">{{ __('screen::general.device_pin_hint') }}</div>
                </x-form.input-div>
            </div>
            <div class="col-12">
                <div class="form-check device-clear-pin-wrap d-none">
                    <input class="form-check-input" type="checkbox" name="clear_pin" value="1" id="device_clear_pin">
                    <label class="form-check-label" for="device_clear_pin">{{ __('screen::general.device_clear_pin') }}</label>
                </div>
            </div>
            <div class="col-12 device-regenerate-wrap d-none">
                <button type="button" class="btn btn-light-primary btn-sm" id="device_regenerate_pairing_btn">
                    <i class="fas fa-qrcode me-1"></i>{{ __('screen::general.device_regenerate_pairing') }}
                </button>
            </div>
        </div>
    </div>
</x-general.modal>

<script>
    (function() {
        const DEVICE_MODAL_TITLE_ADD = @json(__('screen::general.add_device'));
        const DEVICE_MODAL_TITLE_EDIT = @json(__('screen::general.edit_device'));

        function showDevicePairingResult(pairingToken, pairingQrSvg) {
            if (typeof Swal === 'undefined' || !pairingToken) return;
            const copyLabel = @json(__('screen::general.pairing_token_copy'));
            const title = @json(__('screen::general.pairing_created_title'));
            const body = @json(__('screen::general.pairing_created_body'));
            Swal.fire({
                title: title,
                html: '<p class="text-start fs-7 text-gray-700 mb-2">' + body + '</p>' +
                    '<div class="device-pairing-qr-wrap">' + pairingQrSvg + '</div>' +
                    '<label class="form-label fs-8 fw-semibold text-gray-700">' + copyLabel + '</label>' +
                    '<div class="device-pairing-token-box mb-2" id="device_pairing_token_display">' + pairingToken +
                    '</div>' +
                    '<button type="button" class="btn btn-sm btn-light-primary w-100" id="device_pairing_copy_btn">' +
                    copyLabel + '</button>',
                width: 'min(92vw, 400px)',
                padding: '1rem 1rem 1.15rem',
                customClass: {
                    popup: 'device-pairing-swal',
                    htmlContainer: 'device-pairing-swal-html',
                },
                didOpen: function() {
                    const el = document.querySelector('.swal2-html-container');
                    if (el) {
                        el.style.maxHeight = 'none';
                        el.style.overflow = 'visible';
                    }
                },
                confirmButtonText: @json(__('screen::general.pairing_dialog_close')),
            });
            $(document).off('click', '#device_pairing_copy_btn').on('click', '#device_pairing_copy_btn', function() {
                const text = document.getElementById('device_pairing_token_display');
                if (text && navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text.textContent.trim());
                } else if (text) {
                    const range = document.createRange();
                    range.selectNodeContents(text);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                    document.execCommand('copy');
                    sel.removeAllRanges();
                }
            });
        }

        window.showDevicePairingResult = showDevicePairingResult;
        window.setDeviceModalTitleMode = function(mode) {
            const el = document.getElementById('device_modal_title_text');
            if (!el) return;
            el.textContent = mode === 'edit' ? DEVICE_MODAL_TITLE_EDIT : DEVICE_MODAL_TITLE_ADD;
        };

        window.addDeviceModal = function() {
            $('#add_device_modal_form').off('submit').on('submit', function(e) {
                e.preventDefault();
                $('.invalid-feedback').remove();
                $('.is-invalid').removeClass('is-invalid');
                let data = $(this).serializeArray();
                const id = $('#add_device_modal_form [name="id"]').val();
                const isEdit = !!id;

                data.push({
                    name: "_token",
                    value: window.csrfToken
                });
                if (isEdit) {
                    data.push({
                        name: "_method",
                        value: "PATCH"
                    });
                }
                ajaxRequest(isEdit ? `{{ url('/device') }}/${id}` : "{{ route('devices.store') }}", "POST", data).fail(
                    function(data) {
                        $.each(data.responseJSON.errors, function(key, value) {
                            $(`[name='${key}']`).addClass('is-invalid');
                            $(`[name='${key}']`).after('<div class="invalid-feedback">' +
                                value +
                                '</div>');
                        });
                    }).done(function(response) {
                    $('#add_device_modal').modal('toggle');
                    deviceDataTable.ajax.reload();
                    const savedId = $('#add_device_modal_form [name="id"]').val();
                    $('#add_device_modal_form [name="id"]').val('');
                    $('#add_device_modal_form [name="pin"]').val('');

                    let $devices = $('select[name="devices"]');
                    if (response?.data?.id) {
                        let newOption = new Option(response.data.name, response.data.id, true, true);
                        if (!$devices.find(`option[value="${response.data.id}"]`).length) {
                            $devices.append(newOption);
                        }
                    } else if (savedId) {
                        $devices.find(`option[value="${savedId}"]`).text($('#add_device_modal_form [name="code"]').val());
                    }
                    $devices.trigger('change');

                    if (response?.data?.pairing_token && response?.data?.pairing_qr_svg) {
                        showDevicePairingResult(response.data.pairing_token, response.data.pairing_qr_svg);
                    }
                });
            });

            $(document).off('click', '#device_regenerate_pairing_btn').on('click', '#device_regenerate_pairing_btn', function() {
                const id = $('#add_device_modal_form [name="id"]').val();
                if (!id) return;
                const url = `{{ url('/device') }}/${id}/regenerate-screen-pairing`;
                ajaxRequest(url, 'POST', [{
                    name: '_token',
                    value: window.csrfToken
                }]).done(function(response) {
                    if (response?.pairing_token && response?.pairing_qr_svg) {
                        showDevicePairingResult(response.pairing_token, response.pairing_qr_svg);
                    }
                    toastr.success(response?.message || '');
                }).fail(function(xhr) {
                    toastr.error(xhr?.responseJSON?.message ||
                        '{{ __('employee::responses.something_wrong_happened') }}');
                });
            });
        };
    })();
</script>
