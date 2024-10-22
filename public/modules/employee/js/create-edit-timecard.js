function timecardForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);
    const clockInTimePicker = timePicker($("#clockInTime"));
    const clockOutTimePicker = timePicker($("#clockOutTime"));

    $("#clockInTime").on(tempusDominus.Namespace.events.change, function (e) {
        const maxDate = new Date(e.date);
        maxDate.setDate(maxDate.getDate() + 2);

        clockOutTimePicker.updateOptions({
            restrictions: {
                minDate: e.date,
                maxDate: maxDate,
            },
        });
        calculateTimeDifference();
    });

    $("#clockOutTime").on(tempusDominus.Namespace.events.change, function (e) {
        clockInTimePicker.updateOptions({
            restrictions: {
                maxDate: e.date,
            },
        });
        calculateTimeDifference();
    });

    const calculateTimeDifference = () => {
        const clockInDate = clockInTimePicker.dates.picked[0];
        const clockOutDate = clockOutTimePicker.dates.picked[0];

        if (clockInDate && clockOutDate) {
            const diffInMs = clockOutDate.getTime() - clockInDate.getTime();
            const totalMinutes = diffInMs / (1000 * 60);

            let hours = Math.floor(totalMinutes / 60);
            let minutes = Math.floor(totalMinutes % 60);

            minutes = minutes < 10 ? '0' + minutes : minutes;
            hours = hours < 10 ? '0' + hours : hours;

            const formattedTime = `${hours}.${minutes}`;
            $("#hoursWorked").val(formattedTime);
        }
    };

    $(`#${id} input`).on('change', function () {
        let input = $(this);
        validateField(input);
    });

    function validateField(input) {
        let field = input.attr('name');
        let formData = new FormData();
        formData.append(field, input.val());
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
                checkErrors(saveButton);
            },
            error: function (response) {
                input.siblings('.invalid-feedback').remove();
                input.removeClass('is-invalid');

                let errorMsg = response.responseJSON.errors[field];
                if (errorMsg) {
                    input.addClass('is-invalid');
                    input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
                }
                checkErrors(saveButton);
            }
        });
    }
}