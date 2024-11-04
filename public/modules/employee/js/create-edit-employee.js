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


function administrativeUser(administrativeUser, id) {
    let saveButton = $(`#${id}_button`);
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
            $('[name="username"] [name*="[establishment]"], [name*="[dashboardRole]"]')
                .prop('required', true);

        } else {
            $('#active_managment_fields_btn').prop('checked', false);
            $('#active_managment_fields_btn').val(0);
            $('[name="password"], [name="username"], [name*="[establishment]"], [name*="[dashboardRole]"]')
                .prop('required', false);
            $('[name="password"], [name="username"], [name*="[establishment]"], [name*="[dashboardRole]"]').removeClass('is-invalid');
            $('[name*="[establishment]"], [name*="[dashboardRole]"]').siblings('.select2-container').find('.select2-selection').removeClass('is-invalid');
            checkErrors(saveButton);
        }
    });

    $('#accountLocked').on("change", function () {
        if ($(this).is(':checked')) {
            $(this).val(0);
        } else {
            showAlert(Lang.get('responses.change_user_status_warning'),
                Lang.get('general.diactivate'),
                Lang.get('general.cancel'), undefined,
                true, "warning").then(function (t) {
                    if (!t.isConfirmed) {
                        $('#isActive').prop('checked', true);
                    }
                });
        }
    });
}

function employeeForm(id, validationUrl, generatePinUrl) {
    let saveButton = $(`#${id}_button`);
    checkErrors(saveButton);

    if ($('[name="password"], [name="username"], select[name^="dashboard_role_repeater"]').val().length !== 0) {
        $('#dashboard_managment_access').collapse('toggle');
        $('#active_managment_fields_btn').prop('checked', true).val(1);
        $('[name="username"]').prop('required', true);
    }

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
                    if (!t.isConfirmed) {
                        $('#isActive').prop('checked', true);
                    }
                });
        }
    });

    $(`#${id} input, #${id} select, #${id} input[type="file"]`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });

    $('#generate_pin').on('click', function (e) {
        e.preventDefault();
        $('#PIN').removeClass('is-invalid');
        checkErrors(saveButton);
        ajaxRequest(generatePinUrl, 'GET', {}, false, true).done(function (response) {
            $('#PIN').val(response.data);
        });
    });

    function validateRoleRequirement() {
        $('#role_wage_repeater [data-repeater-item]').each(function () {
            const wageInput = $(this).find('input[name*="[wage]"]');
            const roleSelect = $(this).find('select[name*="[role]"]');
            const wageTypeSelect = $(this).find('select[name*="[wage_type]"]');

            // Check if wage input has value
            if (wageInput.val()) {
                roleSelect.attr('required', true);
                wageTypeSelect.attr('required', true);
                // Optionally add an invalid class if it doesn't have a selected value
                if (!roleSelect.val()) {
                    roleSelect.addClass('is-invalid');
                } else {
                    roleSelect.removeClass('is-invalid');
                }
            } else {
                wageTypeSelect.attr('required', false);
                roleSelect.attr('required', false);
                roleSelect.removeClass('is-invalid'); // Remove invalid class if wage is empty
            }
        });
    }


    $('#role_wage_repeater').on('input change', 'input[name*="[wage]"], select change', function () {
        validateRoleRequirement();
    });
}