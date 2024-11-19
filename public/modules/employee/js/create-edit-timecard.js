function timecardForm(id, validationUrl) {
    let saveButton = $(`#${id}_button`);
    const clockInTimePicker = dateTimePicker($("#clock_in_time"));
    const clockOutTimePicker = dateTimePicker($("#clock_out_time"));

    $("#clock_in_time").on(tempusDominus.Namespace.events.change, function (e) {
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

    $("#clock_out_time").on(tempusDominus.Namespace.events.change, function (e) {
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
            $("#hours_worked").val(formattedTime);
        }
    };

    $(`#${id} input`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });
}