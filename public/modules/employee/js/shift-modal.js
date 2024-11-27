function addShiftForm(storeShiftUrl) {
    $('#shift_add_form').on('submit', function (e) {
        e.preventDefault();
        let data = $(this).serializeArray();
        data.push({
            name: 'employee_id',
            value: employeeId
        }, {
            name: 'date',
            value: date
        });
        ajaxRequest(storeShiftUrl, 'POST', data, true, true)
            .done(function () {
                dataTable.ajax.reload();
                $('#shift_add').modal('toggle');
            });
    });
}

function addShiftModal(getShiftUrl) {
    $(document).on('click', '.add-schedule-shift-button', function (e) {
        e.preventDefault();
        employeeId = $(this).data('employee-id');
        const employeeName = $(this).data('employee-name');

        date = $(this).data('date');
        const data = $(this).data();
        const scheduleShiftIds = [];
        const startTimes = [];
        const endTimes = [];
        const establishmentId = [];
        const breakDuration = [];

        ajaxRequest(getShiftUrl, 'GET', {
            employee_id: employeeId,
        }, false, true).done(function (response) {
            establishmentsValues = response.data.establishments;

            let startDayTime = response.data.start_of_day;
            let endDayTime = response.data.end_of_day;

            if (startDayTime && endDayTime) {
                $('.work-time-hint').html(
                    Lang.get('general.day_times_hint', { start: startDayTime, end: endDayTime })
                );
            }

            $('[data-repeater-create]').on('click', function () {
                const newRow = $('select[name*="[establishment]"]').last();

                $.each(establishmentsValues, function (establishmentName, establishmentId) {
                    if (newRow.find(`option[value="${establishmentId}"]`).length ===
                        0) {
                        newRow.append(new Option(establishmentName, establishmentId));
                    }
                });
            });

            $('.work-time-modal-title').html(employeeName + ' | ' + date);

            const sortedKeys = Object.keys(data).sort((a, b) => {
                const numA = parseInt(a.split('-')[1] || 0, 10); // Extract number after "-"
                const numB = parseInt(b.split('-')[1] || 0, 10);
                return numA - numB;
            });

            for (const key of sortedKeys) {
                if (key.startsWith('scheduleShiftId')) {
                    scheduleShiftIds.push(data[key]);
                } else if (key.startsWith('startTime')) {
                    startTimes.push(data[key]);
                } else if (key.startsWith('endTime')) {
                    endTimes.push(data[key]);
                } else if (key.startsWith('establishmentId')) {
                    establishmentId.push(data[key]);
                } else if (key.startsWith('breakDuration')) {
                    breakDuration.push(data[key]);
                }
            }

            const repeaterList = $('[data-repeater-list="shift_repeater"]');
            repeaterList.empty();
            scheduleShiftIds.forEach((shiftId, index) => {

                $('[data-repeater-create]').trigger('click');
                const newItem = repeaterList.find('[data-repeater-item]').last();
                newItem.find('input[name*="[startTime]"]').val(startTimes[index] || '');
                newItem.find('input[name*="[endTime]"]').val(endTimes[index] || '');
                newItem.find('select[name*="[establishment]"]').val(establishmentId[index]).trigger('change');
                newItem.find('input[name*="[shift_id]"]').val(shiftId);
                if (breakDuration[index]) {
                    newItem.find('select[name*="[end_status]"]').val('break').trigger(
                        'change');
                }
            });

            setTimeout(() => {
                $('#shift_add').modal('toggle');
            }, 300);
        });

    });
}

function copyShifts() {
    if (copyShiftFlatpickrInstance) {
        copyShiftFlatpickrInstance.destroy();
    }
    copyShiftFlatpickrInstance = $("#copyShiftDatePicker").flatpickr({
        plugins: [weekSelectPlugin(false)],
        locale: {
            firstDayOfWeek: firstDayOfWeekNumber
        },
        weekNumbers: true,
        clickOpens: false
    });

    let copyDate = $('#copyShiftDatePicker').val().split('-');

    if ($('#copyShiftDatePicker').val()) {
        $('.copy-shifts-modal-title').html(Lang.get('general.copy_warning', { start: copyDate[0], end: copyDate[1] }));
    }
    $('#shift_copy').modal('toggle');

    $('#copyShiftDatePicker').on('change', function () {
        copyDate = $(this).val().split('-');
        $('.copy-shifts-modal-title').html(Lang.get('general.copy_warning', { start: copyDate[0], end: copyDate[1] }));
    });
}

function shiftRepeater() {
    $('#shift_repeater').repeater({
        show: function () {
            $(this).slideDown();
            let startTime;
            let endTime;

            const startTimeInput = $(this).find('input[name*="[startTime]"]');
            const endTimeInput = $(this).find('input[name*="[endTime]"]');

            Inputmask({
                regex: "([0-1][0-9]|2[0-3]):([0-5][0-9])",
                placeholder: "__:__"
            }).mask(startTimeInput[0]);

            Inputmask({
                regex: "([0-1][0-9]|2[0-3]):([0-5][0-9])",
                placeholder: "__:__"
            }).mask(endTimeInput[0]);

            startTimeInput.on('change', function () {
                startTime = startTimeInput.val();
                endTime = endTimeInput.val();

                if (startTime && endTime) {
                    startEndTimeValidate(startTime, endTime, $(this), endTimeInput);
                }
            });

            // Event listener for end time input
            endTimeInput.on('change', function () {
                startTime = startTimeInput.val();
                endTime = endTimeInput.val();

                if (startTime && endTime) {
                    startEndTimeValidate(startTime, endTime, $(this), startTimeInput);
                }
            });

            $(this).find('select[name^="shift_repeater"]').select2({
                minimumResultsForSearch: -1,
            });

            $(this).find('select[name*="[end_status]"]').val('clockout').trigger('change');

            $('#shift_repeater [data-repeater-item]').each(function () {
                const currentErrorId = $(this).find(
                    'select[name*="[end_status]"]').attr('name')
                    .replace(/\[\d+\]\[\w+\]/, (match) => {
                        const index = match.match(/\[(\d+)\]/)[1];
                        return `[${index}][break]`;
                    });
                $(`.repeater-error[data-error-id="${currentErrorId}"]`)
                    .remove();
                checkErrors($('.submit-form-btn'));
            });

            $(this).find('select[name*="[end_status]"]').on('change', function () {
                const name = $(this).attr('name');
                const breakErrorId = name.replace(/\[\d+\]\[\w+\]/, (match) => {
                    const index = match.match(/\[(\d+)\]/)[1];
                    return `[${index}][break]`;
                });

                const lastRepeaterItem = $(
                    '#shift_repeater [data-repeater-item]').last();

                if ($(this).val() === 'break') {
                    if ($(this).closest('[data-repeater-item]').is(lastRepeaterItem)) {
                        addErrorMessage(Lang.get('general.last_element_end_status_error'), breakErrorId);
                        checkErrors($('.submit-form-btn'));
                    }
                } else {
                    $(`.repeater-error[data-error-id="${breakErrorId}"]`).remove();
                    checkErrors($('.submit-form-btn'));
                }
            });
        },
        hide: function (deleteElement) {
            if ($('#shift_repeater [data-repeater-item]').length > 1) {
                $(this).slideUp(deleteElement);
                const name = $(this).find('select[name*="[end_status]"]').attr('name');
                const errorsId = name.replace(/\[\d+\]\[\w+\]/, (match) => {
                    const index = match.match(/\[(\d+)\]/)[1];
                    return `[${index}]`;
                });
                $(`.repeater-error[data-error-id^="${errorsId}"]`).remove();
                checkErrors($('.submit-form-btn'));
            } else {
                showAlert(Lang.get('responses.empty_repeater_warning'),
                    Lang.get('general.ok'),
                    undefined, undefined,
                    false, "error");
            }
        }
    });
}

function startEndTimeValidate(startTime, endTime, thisElement, otherInput) {

    let name = thisElement.attr('name');
    let errorId = name.replace(/\[\d+\]\[\w+\]/, (match) => {
        const index = match.match(/\[(\d+)\]/)[1];
        return `[${index}]`;
    });
    let conflictErrorId = name.replace(/\[\d+\]\[\w+\]/, (match) => {
        const index = match.match(/\[(\d+)\]/)[1];
        return `[${index}][conflict]`;
    });
    if (endTime && startTime) {
        if (startTime >= endTime) {
            thisElement.addClass('is-invalid');
            addErrorMessage(Lang.get('general.startTime_before_endTime_error'), errorId);
        } else {
            thisElement.removeClass('is-invalid');
            otherInput.removeClass('is-invalid');
            $(`.repeater-error[data-error-id="${errorId}"]`).remove();
            checkErrors($('.submit-form-btn'));
        }
    }

    let hasOverlap = false;
    $('#shift_repeater [data-repeater-item]').each(function () {
        const currentStartTime = $(this).find('input[name*="[startTime]"]').val();
        const currentEndTime = $(this).find('input[name*="[endTime]"]').val();

        if ($(this).is(thisElement.closest('[data-repeater-item]'))) return;

        if (currentStartTime && currentEndTime) {
            if (startTime <= currentEndTime && endTime >= currentStartTime) {
                hasOverlap = true;
            }
        }
    });

    if (hasOverlap) {
        addErrorMessage(Lang.get('general.time_overlap_error'), conflictErrorId);
    } else {
        $(`.repeater-error[data-error-id="${conflictErrorId}"]`).remove();
        checkErrors($('.submit-form-btn'));
    }
}