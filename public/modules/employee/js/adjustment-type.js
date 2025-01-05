function initializeSelect2(element, customOptions, repeater = true, lang, addAllowanceTypeUrl) {
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
                                handleNewOption(element, newValue, customOptions, addAllowanceTypeUrl, lang, repeater);
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
                                handleNewOption(element, newValue, customOptions, addAllowanceTypeUrl, lang, repeater);
                            } else {
                                element.val(null).trigger('change');
                            }
                        }
                        isNewOptionHandled = false;
                    });
                }, 0);
            }
        });
    reorderOptions(repeater);
}

function reorderOptions(repeater) {
    if (repeater) {
        const allRepeaters = $(`select[name^="${adjustmentType_type}_repeater"][name$="[adjustment_type]"]`);
        allRepeaters.each(function () {
            const currentElement = $(this);

            const addNewOption = currentElement.find('option[value="add_new"]');

            if (addNewOption.length) {
                addNewOption.detach();
                currentElement.append(addNewOption);
            }
        });
    } else {
        const selectElement = $('select[name="adjustment_type"]');

        const addNewOption = selectElement.find('option[value="add_new"]');

        if (addNewOption.length) {
            addNewOption.detach();
            selectElement.append(addNewOption);
        }
    }
}

function handleNewOption(element, newValue, customOptions, addAllowanceTypeUrl, lang, repeater) {
    const name_lang = lang === 'ar' ? 'name' : 'name_en';
    ajaxRequest(addAllowanceTypeUrl, 'POST', {
        name: newValue,
        name_lang: name_lang,
        type: adjustmentType_type
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

                reorderOptions(repeater, adjustmentType_type);

                element.trigger('change');
                element.select2('open');
            }
        })
        .fail(function () {
            element.val(null).trigger('change');
        });
}

function adjustmentRepeater(type, getTypesUrl, storeTypeUrl, hasInitialValues, callbackFunction = null) {
    $(`#${type}_repeater`).repeater({
        initEmpty: !hasInitialValues,
        ready: function () {
            const $repeater = $(`#${adjustmentType_type}_repeater`);
            $repeater.find(
                `select[name^="${type}_repeater"][name$="[amount_type]"]`)
                .select2({
                    minimumResultsForSearch: -1
                });

            $repeater.find(
                `input[name^="${type}_repeater"][name$="[applicable_date]"]`
            )
                .flatpickr({
                    plugins: [
                        monthSelectPlugin({
                            shorthand: true,
                            dateFormat: "Y-m",
                            altFormat: "F Y"
                        })
                    ]
                });

            const customOptions = new Map();

            $repeater.find(`select[name^="${adjustmentType_type}_repeater"][name$="[adjustment_type]"]`)
                .each(function () {
                    initializeSelect2($(this), customOptions, true, lang,
                        storeTypeUrl);
                });
        },
        show: function () {
            const $this = $(this);
            $this.slideDown();

            ajaxRequest(getTypesUrl, "GET", {
                type: type
            }, false, true, false).done(function (response) {

                let adjustment_type = $this.find(`select[name^="${type}_repeater"][name$="[adjustment_type]"]`);

                adjustment_type.attr('disabled', 'disabled');
                let addNewOption = $this.find(
                    `select[name^="${type}_repeater"][name$="[adjustment_type]"] option[value="add_new"]`
                );

                response.data.forEach(function (item) {
                    let optionText = lang === "ar" ? item.name : (item
                        .name_en || item
                            .name);
                    adjustment_type.append(new Option(optionText,
                        item.id));
                    selectedOption = item.id;
                });

                if (addNewOption.length) {
                    adjustment_type.append(addNewOption);
                }

                adjustment_type.val(null).trigger('change');

                adjustment_type.removeAttr('disabled');
                adjustment_type.trigger('change');
                adjustment_type.removeAttr('disabled', 'disabled');

                $this.find(
                    `select[name^="${type}_repeater"][name$="[amount_type]"]`)
                    .select2({
                        minimumResultsForSearch: -1
                    });

                $this.find(
                    `input[name^="${type}_repeater"][name$="[applicable_date]"]`
                )
                    .flatpickr({
                        plugins: [
                            monthSelectPlugin({
                                shorthand: true,
                                dateFormat: "Y-m",
                                altFormat: "F Y"
                            })
                        ]
                    });
                const customOptions = new Map();

                initializeSelect2($this.find(
                    `select[name^="${type}_repeater"][name$="[adjustment_type]"]`
                ), customOptions, true, lang, storeTypeUrl);

                if (typeof callbackFunction === "function") {
                    callbackFunction();
                }
            }).fail(function () {
                $this.slideUp(function () {
                    $this.remove();
                });
            });
        },

        hide: function (deleteElement) {
            $(this).slideUp(deleteElement);
        }
    });
}