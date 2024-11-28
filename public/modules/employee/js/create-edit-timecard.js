function timecardForm(id, validationUrl, maximum_regular_hours, maximum_overtime_hours) {
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

            $("#hours_worked").val((totalMinutes / 60).toFixed(2));

            let maximum_regular_time = maximum_regular_hours.split(':');
            let maximum_regular_time_in_minutes = parseInt(maximum_regular_time[0] * 60) + parseInt(maximum_regular_time[1]);
            
            let maximum_over_time = maximum_overtime_hours.split(':');
            let maximum_over_time_in_minutes = parseInt(maximum_over_time[0] * 60) + parseInt(maximum_over_time[1]);
            
            let overTime = totalMinutes - maximum_regular_time_in_minutes;
            if (overTime > 0) {
                if (overTime > maximum_over_time_in_minutes) {
                    $("#overtime_hours").val((maximum_over_time_in_minutes / 60).toFixed(2));
                } else {
                    $("#overtime_hours").val((overTime / 60).toFixed(2));
                }
            }else{
                $("#overtime_hours").val(0);
            }
        }
    };

    $(`#${id} input`).on('change', function () {
        let input = $(this);
        validateField(input, validationUrl, saveButton);
    });
}