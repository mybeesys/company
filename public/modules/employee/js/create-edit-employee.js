function roleRepeater() {
    const hasInitialValues = $('select[name="pos_role_repeater[0][posRole]"]').val() !== undefined &&
        $('select[name="pos_role_repeater[0][posRole]"]').val() !== '';

    $('#pos_role_repeater').repeater({
        initEmpty: !hasInitialValues,
        show: function () {
            setTimeout(() => {
                $(this).slideDown();
            }, 1);
            $(this).find('select[name^="pos_role_repeater"]').select2({
                minimumResultsForSearch: -1,
            });
        },
        ready: function () {
            $('select[name^="pos_role_repeater"]').select2({
                minimumResultsForSearch: -1,
            });
        },
        hide: function (deleteElement) {
            $(this).slideUp(deleteElement);
        }
    });
}

function initElements() {
    $('[name="wage_type"]').select2({
        minimumResultsForSearch: -1,
    });

    $('[name="establishment_id"]').select2({
        minimumResultsForSearch: -1,
    });
}
function updateTotalWage() {
    let baseWage = parseFloat($('input[name="wage_amount"]').val()) || 0;
    let percentageTotal = 0;
    let fixedTotal = 0;

    // First calculate all percentages
    $('input[name^="allowance_repeater"][name$="[amount]"]').each(function () {
        const allowanceAmount = parseFloat($(this).closest('.d-flex').find('input[name$="[amount]"]').val()) || 0;
        const amountType = $(this).closest('.d-flex').find('select[name$="[amount_type]"]').val();

        if (amountType === 'percent') {
            percentageTotal += allowanceAmount / 100;
        } else {
            fixedTotal += allowanceAmount;
        }
    });

    // Calculate final total
    let totalWage = baseWage + (baseWage * percentageTotal) + fixedTotal;

    $('#total-wage-span').text(`${Lang.get('fields.total_wage')}: ${totalWage.toFixed(2)}`);
}

$(document).on('change', 'select[name^="allowance_repeater"][name$="[amount_type]"]', function () {
    updateTotalWage();
});

$(document).on('keyup', 'input[name="wage_amount"], input[name^="allowance_repeater"][name$="[amount]"]', function () {
    updateTotalWage();
});

function adjustmentRepeater(type, lang, addAllowanceTypeUrl) {
    $('.employee-adjustments').each(function () {
        const hasInitialValues = $(
            `select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
            .val() !== undefined &&
            $(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
                .val() !== '';

        $(`#${type}_repeater`).repeater({
            initEmpty: !hasInitialValues,
            show: function () {
                const $this = $(this);
                $this.slideDown();

                $this.find(`select[name^="${type}_repeater"][name$="[amount_type]"]`)
                    .select2({
                        minimumResultsForSearch: -1
                    });

                $this.find(`input[name^="${type}_repeater"][name$="[applicable_date]"]`)
                    .flatpickr({
                        plugins: [
                            monthSelectPlugin({
                                shorthand: true,
                                dateFormat: "Y-m",
                                altFormat: "F Y"
                            })
                        ]
                    });

                const customOptions = new Map(); // Moved inside show function

                initializeSelect2($this.find(
                    `select[name^="${type}_repeater"][name$="[adjustment_type]"]`
                ), customOptions, true, lang,
                    addAllowanceTypeUrl, type);
            },

            ready: function () {
                const $repeater = $(`#${type}_repeater`);

                $repeater.find(`select[name^="${type}_repeater"][name$="[amount_type]"]`)
                    .select2({
                        minimumResultsForSearch: -1
                    });

                $repeater.find(`input[name^="${type}_repeater"][name$="[applicable_date]"]`)
                    .flatpickr({
                        plugins: [
                            monthSelectPlugin({
                                shorthand: true,
                                dateFormat: "Y-m",
                                altFormat: "F Y"
                            })
                        ]
                    });

                const customOptions =
                    new Map(); // Also need to define it here for ready function

                $repeater.find(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
                    .each(function () {
                        initializeSelect2($(this), customOptions, true, lang,
                            addAllowanceTypeUrl, type);
                    });
            },

            hide: function (deleteElement) {
                $(this).slideUp(deleteElement);
            }
        });
    });
}

function permissionSetRepeater() {
    const hasInitialValues = $('select[name="dashboard_role_repeater[0][dashboardRole]"]').val() !== undefined &&
        $('select[name="dashboard_role_repeater[0][dashboardRole]"]').val() !== '';

    $('#dashboard_role_repeater').repeater({
        initEmpty: !hasInitialValues,
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
            $(this).slideUp(deleteElement);
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
                Lang.get('general.deactivate'),
                Lang.get('general.cancel'), undefined,
                true, "warning").then(function (t) {
                    if (!t.isConfirmed) {
                        $('#pos_is_active').prop('checked', true);
                    }
                });
        }
    });

    $(`#${id} input, #${id} select, #${id} input[type="file"]`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
        console.log($('[name="wage_amount"]').val());

        if ($('[name="wage_amount"]').val()) {
            $('[name="wage_type"]').attr('required', 'required');
        } else {
            $('[name="wage_type"]').removeAttr('required');
        }
    });

    $('#generate_pin').on('click', function (e) {
        e.preventDefault();
        $('#pin').removeClass('is-invalid');
        checkErrors(saveButton);
        ajaxRequest(generatePinUrl, 'GET', {}, false, true).done(function (response) {
            $('#pin').val(response.data);
        });
    });
}