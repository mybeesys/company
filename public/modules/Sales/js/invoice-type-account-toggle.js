/**
 * Toggle cash account vs. receivable/payable account by invoice type (cash vs. due/credit).
 */
(function ($) {
    'use strict';

    function isDeferredInvoiceType(value) {
        const v = String(value || '').toLowerCase();

        return v === 'due' || v === 'credit';
    }

    function isDocumentOnlyForm() {
        return $('[data-invoice-document-only]').length > 0;
    }

    function applyDocumentOnlyFormUi() {
        $('#div-cash_account').addClass('d-none').hide();
        $('#cash_account').prop('required', false).removeAttr('required').val(null).trigger('change');
        $('#li-payment_info, #tab-content-payment_info').hide();
        setPaymentTabFieldsEnabled(false);
        $('#lable-account_id, #client_l_id').removeClass('required');
        $('#account_id').prop('required', false).removeAttr('required');
    }

    function isInternalConsumptionMode() {
        return typeof window.isInternalConsumptionInvoiceMode === 'function'
            && window.isInternalConsumptionInvoiceMode();
    }

    function getPaymentTabFields() {
        return $('#tab-content-payment_info').find('input, select, textarea');
    }

    function setPaymentTabFieldsEnabled(enabled) {
        const $fields = getPaymentTabFields();
        if (!$fields.length) {
            return;
        }

        // Keep paid_amount submittable; server also sets it for cash invoices.
        $fields.not('#paid_amount').prop('disabled', !enabled);
        if (!enabled) {
            $fields.not('#paid_amount').prop('required', false).removeAttr('required');
        }
    }

    function prepareInvoicePaymentFieldsForSubmit() {
        if (isDocumentOnlyForm() || isInternalConsumptionMode()) {
            setPaymentTabFieldsEnabled(false);

            return;
        }

        const $invoiceType = $('#invoice_type');
        if (!$invoiceType.length) {
            return;
        }

        const isDeferred = isDeferredInvoiceType($invoiceType.val());
        setPaymentTabFieldsEnabled(isDeferred);

        if (!isDeferred && typeof window.updateSalesTotals === 'function') {
            window.updateSalesTotals();
        }
    }

    function applyInvoiceTypeAccountUi(invoiceType) {
        const isDeferred = isDeferredInvoiceType(invoiceType);
        const $cashWrap = $('#div-cash_account');
        const $cash = $('#cash_account');

        if (!$cashWrap.length) {
            return;
        }

        if (isInternalConsumptionMode()) {
            $cashWrap.addClass('d-none').hide();
            $cash.prop('required', false).removeAttr('required');
            $('#li-payment_info, #tab-content-payment_info').hide();
            setPaymentTabFieldsEnabled(false);
            $('#lable-account_id, #client_l_id').removeClass('required');
            $('#account_id, #client_id').prop('required', false).removeAttr('required');

            return;
        }

        if (isDeferred) {
            $cashWrap.addClass('d-none').hide();
            $cash.prop('required', false).removeAttr('required').val(null).trigger('change');

            $('#li-payment_info, #tab-content-payment_info').show();
            setPaymentTabFieldsEnabled(true);
            $('#paid_amount').val(0);

            // Payment-voucher account is optional on credit invoices (partial payments later).
            $('#lable-account_id').removeClass('required');
            $('#account_id').prop('required', false).removeAttr('required');

            if ($('#client_l_id').length) {
                $('#client_l_id').addClass('required');
                $('#client_id').attr('required', 'required');
            }
        } else {
            $cashWrap.removeClass('d-none').show();
            $cash.prop('required', true).attr('required', 'required');

            $('#li-payment_info, #tab-content-payment_info').hide();
            setPaymentTabFieldsEnabled(false);

            $('#lable-account_id').removeClass('required');
            $('#account_id').prop('required', false).removeAttr('required');

            if ($('#client_l_id').length) {
                $('#client_l_id').removeClass('required');
                $('#client_id').removeAttr('required');
            }
        }

        $('#card, #bank_check, #bank_transfer').hide();
    }

    function bindInvoiceTypeAccountToggle() {
        if (isDocumentOnlyForm()) {
            applyDocumentOnlyFormUi();

            return;
        }

        const $invoiceType = $('#invoice_type');
        if (!$invoiceType.length) {
            return;
        }

        const run = function () {
            applyInvoiceTypeAccountUi($invoiceType.val());
        };

        $invoiceType.on('change select2:select', run);
        run();
    }

    $(bindInvoiceTypeAccountToggle);

    $(document).on('click', '#sell_save button[type="submit"]', function () {
        prepareInvoicePaymentFieldsForSubmit();
    });

    window.applyInvoiceTypeAccountUi = applyInvoiceTypeAccountUi;
    window.applyDocumentOnlyFormUi = applyDocumentOnlyFormUi;
    window.bindInvoiceTypeAccountToggle = bindInvoiceTypeAccountToggle;
    window.prepareInvoicePaymentFieldsForSubmit = prepareInvoicePaymentFieldsForSubmit;
    window.refreshInvoiceTypeAccountVisibility = function () {
        applyInvoiceTypeAccountUi($('#invoice_type').val());
    };
})(jQuery);
