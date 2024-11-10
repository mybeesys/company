function datePicker(id, maxDate = null) {
    if (maxDate) {
        restrictions = {
            maxDate: maxDate,
        };
    } else {
        restrictions = {};
    }
    new tempusDominus.TempusDominus($(id)[0], {
        localization: {
            format: "yyyy/MM/dd",
        },
        restrictions: restrictions,
        display: {
            viewMode: "calendar",
            components: {
                decades: true,
                year: true,
                month: true,
                date: true,
                hours: false,
                minutes: false,
                seconds: false
            }
        }
    });
}

function dateTimePicker(id) {
    return new tempusDominus.TempusDominus($(id)[0], {
        useCurrent: false,
        localization: {
            format: "yyyy/MM/dd hh:mm T",
        },
    });
}


function timePicker(element) {
    new tempusDominus.TempusDominus(element, {
        display: {
            viewMode: "clock",
            components: {
                decades: false,
                year: false,
                month: false,
                date: false,
                minutes: true,
                hours: true,
                seconds: false
            },
        },
        localization: {
            format: 'HH:mm'
        },
        useCurrent: false,
    });
}