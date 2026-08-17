(function ($) {
    'use strict';

    function icModeActive() {
        const typeSelected = !!$('#internal_consumption_type_id').val();
        const icToggleOn = $('#toggleInternalConsumption').is(':checked');

        if (icToggleOn && typeSelected) {
            return true;
        }

        return typeof window.isInternalConsumptionInvoiceMode === 'function'
            && window.isInternalConsumptionInvoiceMode();
    }

    function escapeHtml(value) {
        return $('<div>').text(String(value || '')).html();
    }

    function showFeedback(type, message, options) {
        options = options || {};
        const text = String(message || '').trim();
        if (!text) {
            return;
        }

        let $banner = $('#sell-invoice-feedback');
        if (!$banner.length) {
            $banner = $(
                '<div id="sell-invoice-feedback" class="container-fluid px-6 pt-4" style="position:sticky;top:0;z-index:1050;">' +
                '<div class="alert mb-0 shadow-sm" role="alert"></div></div>'
            );
            $('form#sell_save').first().prepend($banner);
        }

        const alertClass = type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger');
        const $alert = $banner.find('.alert')
            .removeClass('alert-success alert-warning alert-danger alert-info')
            .addClass(alertClass);

        const actionUrl = options.actionUrl || options.url || '';
        const actionLabel = options.actionLabel || options.label || '';

        if (actionUrl && actionLabel) {
            $alert
                .removeClass('mb-0')
                .addClass('d-flex flex-column flex-sm-row align-items-start gap-3')
                .html(
                    '<div class="flex-grow-1">' + escapeHtml(text) + '</div>' +
                    '<div class="mt-2 mt-sm-0 flex-shrink-0">' +
                    '<a href="' + escapeHtml(actionUrl) + '" class="btn btn-sm btn-dark">' + escapeHtml(actionLabel) + '</a>' +
                    '</div>'
                );
        } else {
            $alert.removeClass('d-flex flex-column flex-sm-row align-items-start gap-3').addClass('mb-0').text(text);
        }

        $banner.removeClass('d-none').show();

        $('html, body').animate({ scrollTop: $banner.offset().top - 80 }, 200);

        if (typeof toastr !== 'undefined') {
            if (type === 'success') {
                toastr.success(text);
            } else if (type === 'warning') {
                toastr.warning(text);
            } else {
                toastr.error(text);
            }
        }
    }

    function fieldHasValue($field) {
        if (!$field.length) {
            return true;
        }
        const val = $field.val();
        if (Array.isArray(val)) {
            return val.length > 0;
        }
        return val !== null && val !== undefined && String(val).trim() !== '';
    }

    function isVisibleField($field) {
        if (!$field.length) {
            return false;
        }
        if ($field.is('[type="hidden"]')) {
            return false;
        }
        if ($field.closest('.d-none, [hidden]').length) {
            return false;
        }
        return $field.is(':visible') || $field.hasClass('select2-hidden-accessible');
    }

    function enableFieldsForSubmit() {
        const $form = $('#sell_save');
        $form.find(':disabled').each(function () {
            const $field = $(this);
            if ($field.is('#internal_consumption_type_id, #storehouse, #client_id, #cash_account')) {
                $field.prop('disabled', false);
            }
        });

        if ($('#internal_consumption_type_id').hasClass('select2-hidden-accessible')) {
            $('#internal_consumption_type_id').trigger('change.select2');
        }
    }

    function validateSellInvoiceForm() {
        const $form = $('#sell_save');
        const icMode = icModeActive();
        const precheck = window.invoicePrecheckConfig || {};
        const messages = [];

        $form.find('.is-invalid').removeClass('is-invalid');

        if (icMode && !fieldHasValue($('#internal_consumption_type_id'))) {
            $('#internal_consumption_type_id').addClass('is-invalid');
            messages.push(
                precheck.messages?.internalConsumptionTypeRequired
                || 'Please select an internal expense type.'
            );
        }

        if (!icMode) {
            const missingAccounts = Array.isArray(precheck.missingAccounts) ? precheck.missingAccounts : [];
            if (missingAccounts.length > 0) {
                const header = precheck.messages?.missingAccountsHeader || 'Accounting setup is incomplete:';
                messages.push(header + ' ' + missingAccounts.join(', '));
            }

            const $client = $form.find('#client_id');
            const clientId = $client.val();
            if (clientId) {
                const $option = $client.find(':selected');
                const hasAccount = String($option.data('has-account') ?? '');
                const accountId = $option.data('account-id');
                const linked = hasAccount === '1' || hasAccount === 'true'
                    || (accountId !== undefined && accountId !== null && String(accountId).trim() !== '' && Number(accountId) > 0);
                const metadataPresent = hasAccount !== '' || (accountId !== undefined && accountId !== null);
                if (metadataPresent && !linked) {
                    $client.addClass('is-invalid');
                    messages.push(precheck.messages?.contactMissingAccount || 'Customer has no linked accounting account.');
                }
            }
        }

        let hasProductLine = false;
        let missingUnit = false;
        $form.find('#salesTable tbody tr.sales-line-row').each(function () {
            const $row = $(this);
            const productId = $row.find('[name$="[products_id]"]').val();
            if (!productId) {
                return;
            }
            hasProductLine = true;
            const unitValue = $row.find('[name*="[unit]"]').val();
            if (!unitValue) {
                missingUnit = true;
                $row.find('[name*="[unit]"]').addClass('is-invalid');
            }
        });

        if (!hasProductLine) {
            messages.push(precheck.messages?.missingProductLine || 'Add at least one product line.');
        } else if (missingUnit) {
            messages.push(precheck.messages?.missingUnit || 'Select a unit for each product line.');
        }

        $form.find('[required]').each(function () {
            const $field = $(this);
            if (!isVisibleField($field)) {
                return;
            }
            if (icMode && (
                $field.is('#cash_account')
                || $field.is('#client_id')
                || $field.is('#invoiced_discount_type')
            )) {
                return;
            }
            if ($field.is('.product-select')) {
                const productId = $field.closest('tr').find('[name$="[products_id]"]').val();
                if (!productId) {
                    return;
                }
            }
            if (!fieldHasValue($field)) {
                $field.addClass('is-invalid');
                if (!messages.length) {
                    messages.push(precheck.messages?.requiredFields || 'Please fill in all required fields.');
                }
            }
        });

        if (messages.length) {
            showFeedback('error', messages[0]);
            return false;
        }

        return true;
    }

    function prepareSellInvoiceSubmit(action, status) {
        const icMode = icModeActive();

        enableFieldsForSubmit();

        $('#sell_save input[name="action"]').remove();
        if (action) {
            $('<input>', { type: 'hidden', name: 'action', value: action }).appendTo('#sell_save');
        }

        if (status) {
            $('#sell_save input[name="status"]').val(status);
        }

        if (icMode) {
            $('#transaction_purpose').val('internal_consumption');
            $('#cash_account, #client_id, #invoiced_discount_type').prop('required', false).removeAttr('required');
            if (typeof window.reapplyInternalConsumptionPricing === 'function') {
                window.reapplyInternalConsumptionPricing();
            }
        } else {
            $('#transaction_purpose').val('standard');
        }
    }

    function persistFlashForRedirect(type, message) {
        if (!message) {
            return;
        }
        try {
            sessionStorage.setItem('sellInvoiceFlash', JSON.stringify({ type: type, message: message }));
        } catch (e) {
            // ignore storage errors
        }
    }

    function submitSellInvoice(action, status) {
        if (!validateSellInvoiceForm()) {
            return false;
        }

        prepareSellInvoiceSubmit(action, status);

        const form = document.getElementById('sell_save');
        if (!form) {
            showFeedback('error', 'Invoice form not found.');
            return false;
        }

        const $submitButtons = $('#sell_save [data-action], #sell_save button[type="submit"]');
        $submitButtons.prop('disabled', true);

        const formData = new FormData(form);
        const csrf = $('meta[name="csrf-token"]').attr('content');
        if (csrf && !formData.get('_token')) {
            formData.append('_token', csrf);
        }

        const formUrl = form.getAttribute('action') || $('#sell_save').attr('action');
        if (!formUrl) {
            showFeedback('error', 'Invoice form action URL is missing.');
            $submitButtons.prop('disabled', false);
            return false;
        }

        fetch(formUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(async function (response) {
                const contentType = response.headers.get('content-type') || '';
                let payload = null;

                if (contentType.includes('application/json')) {
                    payload = await response.json().catch(function () {
                        return null;
                    });
                }

                if (!response.ok) {
                    const message = payload?.message
                        || (payload?.errors && Object.values(payload.errors).flat()[0])
                        || 'Could not save invoice.';
                    const action = payload?.action || null;
                    showFeedback('error', message, {
                        actionUrl: action?.url,
                        actionLabel: action?.label,
                    });
                    return;
                }

                if (payload && payload.redirect) {
                    persistFlashForRedirect(payload.status === 'error' ? 'error' : 'success', payload.message);
                    window.location.assign(payload.redirect);
                    return;
                }

                window.location.reload();
            })
            .catch(function () {
                showFeedback('error', 'Network error while saving. Please try again.');
            })
            .finally(function () {
                $submitButtons.prop('disabled', false);
            });

        return true;
    }

    window.showSellInvoiceFeedback = showFeedback;
    window.validateSellInvoiceForm = validateSellInvoiceForm;
    window.submitSellInvoice = submitSellInvoice;

    $(document).on('submit', '#sell_save', function (e) {
        e.preventDefault();

        const submitter = e.originalEvent && e.originalEvent.submitter;
        const $submitter = submitter ? $(submitter) : $();
        const isDraftButton = $submitter.length
            && $submitter.is('button[type="submit"]')
            && !$submitter.is('[data-action]');

        if (isDraftButton) {
            submitSellInvoice(null, 'draft');
            return false;
        }

        submitSellInvoice('save', 'approved');
        return false;
    });

    $(document).on('click', '#sell_save [data-action]', function (e) {
        e.preventDefault();
        submitSellInvoice($(this).data('action'), 'approved');
    });

    $(function () {
        try {
            const stored = sessionStorage.getItem('sellInvoiceFlash');
            if (stored) {
                sessionStorage.removeItem('sellInvoiceFlash');
                const data = JSON.parse(stored);
                showFeedback(data.type || 'success', data.message);
            }
        } catch (e) {
            // ignore storage errors
        }

        const flash = window.sellInvoicePageFlash || {};
        (flash.errors || []).forEach(function (msg) {
            showFeedback('error', msg);
        });
        (flash.success || []).forEach(function (msg) {
            showFeedback('success', msg);
        });
    });
})(jQuery);
