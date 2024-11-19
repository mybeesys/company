function roleRepeater() {
    const hasInitialValues = $('select[name="role_wage_repeater[0][posRole]"]').val() !== undefined &&
        $('select[name="role_wage_repeater[0][posRole]"]').val() !== '';

    $('#role_wage_repeater').repeater({
        initEmpty: !hasInitialValues,
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
            $(this).slideUp(deleteElement);
        }
    });
}

function allowanceRepeater(addAllowanceTypeUrl, lang) {
    // Keep track of all custom options
    const customOptions = new Map();

    function initializeSelect2(element) {
        const select2Config = {
            tags: true,
            createTag: function (params) {
                const term = (params.term || '').trim();
                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true
                };
            },
            // Add existing custom options to new select2 instances
            data: Array.from(customOptions.values())
        };
        element.select2(select2Config)
            .on('select2:select', handleTagSelection);
    }

    function handleTagSelection(e) {
        const data = e.params.data;

        if (!data.newTag) {
            return;
        }

        const name_lang = lang === 'ar' ? 'name' : 'name_en';
        const $select = $(e.target);

        ajaxRequest(addAllowanceTypeUrl, 'POST', { name: data.text, name_lang: name_lang })
            .done(function (response) {
                if (response.id) {
                    const newOption = {
                        id: response.id,
                        text: data.text
                    };
                    customOptions.set(response.id, newOption);

                    $('select[name*="[allowance_type]"]').each(function () {
                        const $select = $(this);

                        const option = new Option(newOption.text, newOption.id, false, false);
                        $select.append(option);

                        // Update the current select2 instance with the selected value
                        if (this === e.target) {
                            $select.val(response.id).trigger('change');
                        }
                    });
                }
            })
            .fail(function () {
                $select.val(null).trigger('change');
            });
    }
    const hasInitialValues = $('select[name="allowance_repeater[0][allowance_type]"]').val() !== undefined &&
        $('select[name="allowance_repeater[0][allowance_type]"]').val() !== '';
    $('#allowance_repeater').repeater({
        initEmpty: !hasInitialValues,

        show: function () {
            const $this = $(this);
            $this.slideDown();
            $this.find('select[name*="[amount_type]"]').select2({
                minimumResultsForSearch: -1,
            });
            $this.find('input[name*="[applicable_date]"]').flatpickr();
            initializeSelect2($this.find('select[name*="[allowance_type]"]'));
        },

        ready: function () {
            $('select[name*="[amount_type]"]').select2({
                minimumResultsForSearch: -1,
            });
            $('input[name*="[applicable_date]"]').flatpickr();
            initializeSelect2($('select[name*="[allowance_type]"]'));
        },

        hide: function (deleteElement) {
            $(this).slideUp(deleteElement);
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
                showAlert(Lang.get('responses.empty_repeater_warning'),
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
        $('#dashboard_management_access').collapse('toggle');
        $('#ems_access').prop('checked', true).val(1);
        $('[name="username"], [name^="dashboard_role_repeater"][name$="[dashboardRole]"]').prop('required', true)
    }

    $('.active-management-fields').on('click', function (e) {
        if ($(this).attr("aria-expanded") == 'true') {
            $('#ems_access').prop('checked', true);
            $('#ems_access').val(1);

            if (!administrativeUser) {
                $('[name="password"]').prop('required', true);
            }
            $('[name="username"], [name^="dashboard_role_repeater"][name$="[dashboardRole]"]')
                .prop('required', true);
        } else {
            $('#ems_access').prop('checked', false);
            $('#ems_access').val(0);
            $('[name="password"], [name="username"], [name^="dashboard_role_repeater"][name$="[dashboardRole]"]')
                .prop('required', false);
            $('[name="password"], [name="username"], [name^="dashboard_role_repeater"][name$="[dashboardRole]"]').removeClass('is-invalid');
            $('[name^="dashboard_role_repeater"][name$="[dashboardRole]"]').siblings('.select2-container').find('.select2-selection').removeClass('is-invalid');
            checkErrors(saveButton);
        }
    });
}



function employeeForm(id, validationUrl, generatePinUrl) {
    let saveButton = $(`#${id}_button`);
    checkErrors(saveButton);

    if ($('[name="password"], [name="username"], select[name^="dashboard_role_repeater"]').val().length !== 0) {
        $('#dashboard_management_access').collapse('toggle');
        $('#ems_access').prop('checked', true).val(1);
        $('[name="username"]').prop('required', true);
    }

    $('[name="permissionSet"]').select2({
        minimumResultsForSearch: -1
    });

    $('#pos_is_active').on("change", function () {
        if ($(this).is(':checked')) {
            $(this).val(1);
        } else {
            showAlert(Lang.get('responses.change_employee_status_warning'),
                Lang.get('general.diactivate'),
                Lang.get('general.cancel'), undefined,
                true, "warning").then(function (t) {
                    if (!t.isConfirmed) {
                        $(this).prop('checked', true);
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

    function validatePosRoleRequirement() {
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

    function validateDashboardRoleRequirement() {
        $('#dashboard_role_repeater [data-repeater-item]').each(function () {
            const wageInput = $(this).find('input[name*="[wage]"]');
            const roleSelect = $(this).find('select[name*="[dashboardRole]"]');
            const wageTypeSelect = $(this).find('select[name*="[wage_type]"]');

            // Check if wage input has value
            if (wageInput.val()) {
                roleSelect.attr('required', true);
                wageTypeSelect.attr('required', true);

                if (!roleSelect.val()) {
                    roleSelect.addClass('is-invalid');
                } else {
                    roleSelect.removeClass('is-invalid');
                }
            } else {
                wageTypeSelect.attr('required', false);
                roleSelect.attr('required', false);
                roleSelect.removeClass('is-invalid');
            }
        });
    }

    $('#dashboard_role_repeater').on('input change', 'input[name*="[wage]"], select change', function () {
        validateDashboardRoleRequirement();
    });

    $('#role_wage_repeater').on('input change', 'input[name*="[wage]"], select change', function () {
        validatePosRoleRequirement();
    });
}