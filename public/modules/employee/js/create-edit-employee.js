function roleRepeater() {
    $('#role_wage_repeater').repeater({
        initEmpty: false,
        show: function () {
            $(this).slideDown();
            $(this).find('select[name^="role_wage_repeater"]').select2({
                minimumResultsForSearch: -1,
            });
        },
        ready: function () {
            $('select[name^="role_wage_repeater"]').select2({
                minimumResultsForSearch: -1,
            });
        },
        hide: function (deleteElement) {
            if ($('#role_wage_repeater [data-repeater-item]').length > 1) {
                $(this).slideUp(deleteElement);
            } else {
                showAlert(Lang.get('responses.emptyRepeaterwarning'),
                    Lang.get('general.ok'),
                    undefined, undefined,
                    false, "error");
            }
        }
    });
}

function permissionSetRepeater() {
    $('#dashboard_role_repeater').repeater({
        initEmpty: false,
        show: function () {
            $(this).slideDown();

            $(this).find('select[name^="dashboard_role_repeater"]').select2({
                minimumResultsForSearch: -1,
            });
        },
        ready: function () {

            $('select[name^="dashboard_role_repeater"]').select2({
                minimumResultsForSearch: -1,
            });
        },
        hide: function (deleteElement) {
            if ($('#dashboard_role_repeater [data-repeater-item]').length > 1) {
                $(this).slideUp(deleteElement);
            } else {
                showAlert(Lang.get('responses.emptyRepeaterwarning'),
                    Lang.get('general.ok'),
                    undefined, undefined,
                    false, "error");
            }
        }
    });
}


function administrativeUser(administrativeUser) {

    if (administrativeUser) {
        $('#dashboard_managment_access').collapse('toggle');
        $('#active_managment_fields_btn').prop('checked', true).val(1);
        $('[name="username"]').prop('required', true);
    }

    $('.active-managment-fields').on('click', function (e) {
        if ($(this).attr("aria-expanded") == 'true') {
            $('#active_managment_fields_btn').prop('checked', true);
            $('#active_managment_fields_btn').val(1);

            if (!administrativeUser) {
                $('[name="password"]').prop('required', true);
            }
            $('[name="username"]')
                .prop('required', true);

        } else {
            $('#active_managment_fields_btn').prop('checked', false);
            $('#active_managment_fields_btn').val(0);
            $('[name="password"], [name="username"]')
                .prop('required', false);
        }
    });
}


function employeeForm(id, validationUrl, generatePinUrl) {
    let saveButton = $(`#${id}_button`);
    saveButton.prop('disabled', true);

    $('[name="permissionSet"]').select2({
        minimumResultsForSearch: -1
    });

    $('#isActive').on("change", function () {
        if ($(this).is(':checked')) {
            $(this).val(1);
        } else {
            showAlert(Lang.get('responses.change_employee_status_warning'),
                Lang.get('general.diactivate'),
                Lang.get('general.cancel'), undefined,
                true, "warning").then(function (t) {
                    if (t.isConfirmed) {
                        $(this).val(1);
                    } else {
                        $(this).val(0);
                        $('#isActive').prop('checked', true);
                    }
                });
        }
    });

    $(`#${id} input, #${id} select, #${id} input[type="file"]`).on('change', function () {
        let input = $(this);
        validateField(input);
    });

    $('#generate_pin').on('click', function (e) {
        e.preventDefault();
        $('#PIN').removeClass('is-invalid');
        $.ajax({
            url: generatePinUrl,
            type: 'GET',
            success: function (response) {
                $('#PIN').val(response.data);
            },
            error: function () {
                showAlert(Lang.get('responses.something_wrong_happened'),
                    Lang.get('general.try_again'),
                    undefined, undefined,
                    false, "error");
            }
        });
    });

    function validateField(input) {
        let field = input.attr('name');
        let formData = new FormData();
        formData.append(field, input[0].files ? input[0].files[0] : input.val());
        formData.append("_token", window.csrfToken);

        $.ajax({
            url: validationUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function () {
                input.siblings('.invalid-feedback ').remove();
                input.removeClass('is-invalid');
                input.siblings('.select2-container').find('.select2-selection').removeClass('is-invalid');
                $('#image_error').removeClass('d-block');
                checkErrors();
            },
            error: function (response) {
                input.siblings('.invalid-feedback').remove();
                input.removeClass('is-invalid');
                input.siblings('.select2-container').find('.select2-selection').removeClass('is-invalid');
                $('#image_error').removeClass('d-block');
                if (response.responseJSON) {
                    let errorMsg = response.responseJSON.errors[field];
                    if (errorMsg) {
                        input.addClass('is-invalid');
                        input.siblings('.select2-container').find('.select2-selection').addClass('is-invalid');
                        if (input.attr('type') === 'file') {
                            input.closest('div').after(
                                '<div class="invalid-feedback d-block" id="image_error">' +
                                errorMsg[0] + '</div>');
                        } else {
                            input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
                        }
                    }
                }
                checkErrors();
            }
        });
    }

    function checkErrors() {
        if ($('.is-invalid').length > 0) {
            saveButton.prop('disabled', true);
        } else {
            saveButton.prop('disabled', false);
        }
    }

    function validateRoleRequirement() {
        $('#role_wage_repeater [data-repeater-item]').each(function () {
            const wageInput = $(this).find('input[name*="[wage]"]');
            const roleSelect = $(this).find('select[name*="[role]"]');

            // Check if wage input has value
            if (wageInput.val()) {
                roleSelect.attr('required', true);
                // Optionally add an invalid class if it doesn't have a selected value
                if (!roleSelect.val()) {
                    roleSelect.addClass('is-invalid');
                } else {
                    roleSelect.removeClass('is-invalid');
                }
            } else {
                roleSelect.attr('required', false);
                roleSelect.removeClass('is-invalid'); // Remove invalid class if wage is empty
            }
        });
    }


    $('#role_wage_repeater').on('input change', 'input[name*="[wage]"], select change', function () {
        validateRoleRequirement();
    });
}