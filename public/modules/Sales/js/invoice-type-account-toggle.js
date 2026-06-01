/**
 * Toggle cash account vs. receivable/payable account by invoice type (cash vs. due/credit).
 */
(function ($) {
    'use strict';

    function isDeferredInvoiceType(value) {
        const v = String(value || '').toLowerCase();

        return v === 'due' || v === 'credit';
    }

    function applyInvoiceTypeAccountUi(invoiceType) {
        const isDeferred = isDeferredInvoiceType(invoiceType);
        const $cashWrap = $('#div-cash_account');
        const $cash = $('#cash_account');

        if (!$cashWrap.length) {
            return;
        }

        if (isDeferred) {
            $cashWrap.addClass('d-none').hide();
            $cash.prop('required', false).removeAttr('required').val(null).trigger('change');

            $('#li-payment_info, #tab-content-payment_info').show();
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

    window.applyInvoiceTypeAccountUi = applyInvoiceTypeAccountUi;
    window.bindInvoiceTypeAccountToggle = bindInvoiceTypeAccountToggle;
})(jQuery);
