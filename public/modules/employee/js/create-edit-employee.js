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

function allowanceRepeater(type, addAllowanceTypeUrl, lang) {
    const customOptions = new Map();

    function initializeSelect2(element) {
        const addNewOption = {
            id: 'add_new',
            text: lang === 'ar' ? 'إضافة خيار جديد' : 'Add New Option',
            addNew: true
        };
        const allOptions = [addNewOption, ...Array.from(customOptions.values())];
        const select2Config = {
            tags: false,
            data: allOptions,
            templateResult: function (option) {
                if (option.addNew) {
                    return $(`
            <div class="add-new-option">
                <i class="fas fa-plus me-2"></i>
                <span>${option.text}</span>
            </div>
        `);
                }
                return option.text;
            },
            templateSelection: function (option) {
                if (option.addNew) {
                    return $(
                        `<input type="text" class="select2-add-new-input" placeholder="${lang === 'ar' ? 'اكتب الخيار الجديد' : 'Type new option...'}"/>`
                    );
                }
                return option.text;
            }
        };

        element.select2(select2Config)
            .on('select2:select', function (e) {
                const data = e.params.data;
                if (data.addNew) {
                    setTimeout(() => {
                        const input = $('.select2-add-new-input');
                        input.focus();
                        let isNewOptionHandled = false;

                        input.on('keydown', function (e) {
                            if (e.which === 13) {
                                const newValue = $(this).val();
                                if (newValue.trim()) {
                                    handleNewOption(element, newValue);
                                    isNewOptionHandled = true;
                                }
                            }
                            if (e.which === 32) {
                                e.stopPropagation();
                            }
                        });
                        input.on('blur', function () {
                            if (!isNewOptionHandled) {
                                const newValue = $(this).val().trim();
                                if (newValue) {
                                    handleNewOption(element, newValue);
                                } else {
                                    element.val(null).trigger('change');
                                }
                            }
                            isNewOptionHandled = false;
                        });
                    }, 0);
                }
            });
        reorderOptions();
    }

    function reorderOptions() {
        const allRepeaters = $(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`);
        allRepeaters.each(function () {
            const currentElement = $(this);
            const addNewOption = currentElement.find('option[value="add_new"]');
            if (addNewOption.length) {
                addNewOption.detach();
                currentElement.append(addNewOption);
            }
        });
    }

    function handleNewOption(element, newValue) {
        const name_lang = lang === 'ar' ? 'name' : 'name_en';

        ajaxRequest(addAllowanceTypeUrl, 'POST', {
            name: newValue,
            name_lang: name_lang,
            type: type
        })
            .done(function (response) {
                if (response.id) {
                    const newOption = {
                        id: response.id,
                        text: newValue
                    };
                    customOptions.set(response.id, newOption);

                    const newSelectOption = new Option(newOption.text, newOption.id, true, true);
                    element.append(newSelectOption);

                    reorderOptions();

                    element.trigger('change');
                    element.select2('open');
                }
            })
            .fail(function () {
                element.val(null).trigger('change');
            });
    }

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

                initializeSelect2($this.find(
                    `select[name^="${type}_repeater"][name$="[adjustment_type]"]`
                ));
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

                $repeater.find(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`)
                    .each(function () {
                        initializeSelect2($(this));
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