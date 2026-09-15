{{-- Include from @section('script') AFTER jQuery (layouts.js-references). --}}
<script>
(function ($) {
    if (window.__mybeeCreateAccountModalBound) {
        return;
    }
    window.__mybeeCreateAccountModalBound = true;

    var nextGlCodeUrl = @json(route('next-gl-code'));
    var storeAccountUrl = @json(route('store-account'));
    var genericError = @json(__('messages.something_went_wrong'));
    var glRequest = null;

    function glInput() {
        return $('#create_gl_code_ajax');
    }

    function parentSelect() {
        return $('#kt_modal_create_account').find('select[name="account_id"]').first();
    }

    window.fillNextGlCodeForParent = function (parentId) {
        var $input = glInput();
        if (!$input.length) {
            return;
        }
        if (!parentId) {
            $input.val('');
            return;
        }

        if (glRequest && typeof glRequest.abort === 'function') {
            glRequest.abort();
        }

        $input.prop('readonly', true).val('...');

        glRequest = $.ajax({
            url: nextGlCodeUrl,
            method: 'GET',
            dataType: 'json',
            data: { parent_account_id: parentId },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).done(function (resp) {
            $input.val((resp && resp.gl_code) ? resp.gl_code : '');
        }).fail(function (xhr, status) {
            if (status === 'abort') {
                return;
            }
            $input.val('');
        }).always(function () {
            $input.prop('readonly', false);
            glRequest = null;
        });
    };

    function resolveAccountSelect() {
        if (window.__lastAccountingAccountSelect && document.body.contains(window.__lastAccountingAccountSelect)) {
            return $(window.__lastAccountingAccountSelect);
        }

        var $byId = $('select#account_id.kt_ecommerce_select2_account');
        if ($byId.length) {
            return $byId.first();
        }

        var $all = $('select.kt_ecommerce_select2_account').filter(function () {
            return $(this).closest('#kt_modal_create_account').length === 0;
        });

        var $empty = $all.filter(function () {
            return !$(this).val();
        }).first();

        return $empty.length ? $empty : $all.first();
    }

    window.applyCreatedAccountingAccount = function (account) {
        if (!account || !account.id) {
            return;
        }

        var $sel = resolveAccountSelect();
        if (!$sel.length) {
            return;
        }

        var id = String(account.id);
        var text = account.text || account.name_ar || account.name_en || id;

        if ($sel.find('option[value="' + id + '"]').length === 0) {
            $sel.append(new Option(text, id, true, true));
        }

        $sel.val(id).trigger('change');
    };

    $(document).on('select2:opening', 'select.kt_ecommerce_select2_account', function () {
        window.__lastAccountingAccountSelect = this;
    });

    $(document).on('click', '#addNewAccountBtn', function () {
        var $open = $('select.kt_ecommerce_select2_account').filter(function () {
            return $(this).data('select2') && $(this).select2('isOpen');
        }).first();
        if ($open.length) {
            window.__lastAccountingAccountSelect = $open.get(0);
        }
    });

    $(document).on('change select2:select', '#kt_ecommerce_select2_account_type', function () {
        window.fillNextGlCodeForParent($(this).val());
    });

    $(document).on('shown.bs.modal', '#kt_modal_create_account', function () {
        var $modal = $(this);
        var $parent = $modal.find('#kt_ecommerce_select2_account_type');

        $('#addAccountFormErrors').addClass('d-none').empty();

        if ($parent.length && !$parent.data('select2')) {
            $parent.select2({
                dropdownParent: $modal,
                width: '100%'
            });
        }

        var parentId = $parent.val();
        if (parentId) {
            window.fillNextGlCodeForParent(parentId);
        } else {
            glInput().val('');
        }
    });

    $(document).on('submit', '#addAccountForm', function (e) {
        e.preventDefault();

        var $form = $(this);
        $('#submitBtn .indicator-label').hide();
        $('#submitBtn .indicator-progress').show();
        $('#addAccountFormErrors').addClass('d-none').empty();

        $.ajax({
            url: storeAccountUrl,
            method: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (response) {
                var account = response && response.account ? response.account : null;
                var $modal = $('#kt_modal_create_account');

                $modal.one('hidden.bs.modal', function () {
                    window.applyCreatedAccountingAccount(account);
                });

                if ($modal.hasClass('show')) {
                    $modal.modal('hide');
                } else {
                    window.applyCreatedAccountingAccount(account);
                }

                $form[0].reset();
                glInput().val('');
                if ($('#kt_ecommerce_select2_account_type').data('select2')) {
                    $('#kt_ecommerce_select2_account_type').val(null).trigger('change');
                }
            },
            error: function (xhr) {
                var msg = genericError;
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        msg = [].concat.apply([], Object.values(xhr.responseJSON.errors)).join('<br>');
                    }
                }
                $('#addAccountFormErrors').removeClass('d-none').html(msg);
            },
            complete: function () {
                $('#submitBtn .indicator-label').show();
                $('#submitBtn .indicator-progress').hide();
            }
        });
    });
})(jQuery);
</script>
