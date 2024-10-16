function datePicker(id) {
    new tempusDominus.TempusDominus($(id)[0], {
        localization: {
            format: "yyyy/MM/dd",
        },
        restrictions: {
            maxDate: new Date(),
        },
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