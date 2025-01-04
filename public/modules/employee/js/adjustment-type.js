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