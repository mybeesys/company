function handleFilters(tableUrl) {
    const employee = $('[data-kt-filter="employee_filter"]');
    const establishment = $('[data-kt-filter="establishment_filter"]');
    const format = $('[data-kt-filter="format_filter"]');
    const role = $('[data-kt-filter="role_filter"]');

    role.on('change', function() {
        filterRoleId = $(this).val();
        filter();
    });

    establishment.on('change', function() {
        filterEstablishmentId = $(this).val();
        filter();
    });

    employee.on('change', function() {
        filterEmployeeStatus = $(this).val();
        filter();
    });

    format.on('change', function() {
        filterFormat = $(this).val();
        filter();
    });

    function filter() {
        dataTable.ajax.url(tableUrl + '?' + $.param({
            table_type: tableType,
            filter_role: filterRoleId,
            filter_establishment: filterEstablishmentId,
            filter_employee_status: filterEmployeeStatus,
            format: filterFormat
        })).load();
    }
}



function weekSelectPlugin(reinitialize_table) {
    let startDate;
    let endDate;
    return function (fp) {
        function onDayHover(event) {
            var day = event.target;
            if (!day.classList.contains("flatpickr-day")) return;

            var days = fp.days.childNodes;
            var dayIndex = day.$i;
            var dayIndSeven = dayIndex / 7;
            var weekStartDay = days[7 * Math.floor(dayIndSeven)].dateObj;
            var weekEndDay = days[7 * Math.ceil(dayIndSeven + 0.01) - 1].dateObj;

            for (var i = days.length; i--;) {
                var day_1 = days[i];
                var date = day_1.dateObj;
                if (date > weekEndDay || date < weekStartDay) {
                    day_1.classList.remove("inRange");
                } else {
                    day_1.classList.add("inRange");
                }
            }
        }

        function highlightWeek() {
            if (fp.selectedDateElem) {
                fp.weekStartDay = fp.days.childNodes[7 * Math.floor(fp.selectedDateElem.$i / 7)].dateObj;
                fp.weekEndDay = fp.days.childNodes[7 * Math.ceil(fp.selectedDateElem.$i / 7 + 0.01) - 1]
                    .dateObj;

                const start = fp.weekStartDay;
                const end = fp.weekEndDay;
                const formattedStart =
                    `${String(start.getDate()).padStart(2, '0')}/${String(start.getMonth() + 1).padStart(2, '0')}/${start.getFullYear()}`;
                const formattedEnd =
                    `${String(end.getDate()).padStart(2, '0')}/${String(end.getMonth() + 1).padStart(2, '0')}/${end.getFullYear()}`;

                fp.input.value = `${formattedStart} - ${formattedEnd}`;
            }

            var days = fp.days.childNodes;
            for (var i = days.length; i--;) {
                var date = days[i].dateObj;
                if (date >= fp.weekStartDay && date <= fp.weekEndDay) {
                    days[i].classList.add("week", "selected");
                }
            }
        }

        function setDefaultWeek() {
            const today = new Date();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - ((today.getDay() + 1) % 7));
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            startDate =
                `${startOfWeek.getFullYear()}-${String(startOfWeek.getMonth() + 1).padStart(2, '0')}-${String(startOfWeek.getDate()).padStart(2, '0')}`;
            endDate =
                `${endOfWeek.getFullYear()}-${String(endOfWeek.getMonth() + 1).padStart(2, '0')}-${String(endOfWeek.getDate()).padStart(2, '0')}`;
            fp.setDate([startOfWeek, endOfWeek]);
            highlightWeek();
            if (reinitialize_table) {
                initTable(tableUrl, startDate, endDate);
            }
        }

        function clearHover() {
            var days = fp.days.childNodes;
            for (var i = days.length; i--;) {
                days[i].classList.remove("inRange");
            }
        }

        function onReady() {
            if (fp.daysContainer !== undefined) {
                fp.daysContainer.addEventListener("mouseover", onDayHover);
                setDefaultWeek(); // Set the current week as the default selection on load
            }
        }

        function onDestroy() {
            if (fp.daysContainer !== undefined) {
                fp.daysContainer.removeEventListener("mouseover", onDayHover);
            }
        }

        return {
            onValueUpdate: highlightWeek,
            onMonthChange: highlightWeek,
            onYearChange: highlightWeek,
            onClose: clearHover,
            onParseConfig: function () {
                fp.config.mode = "single";
                fp.config.enableTime = false;
                fp.config.dateFormat = "d/m/Y - d/m/Y";
                fp.config.altFormat = "d/m/Y - d/m/Y";
            },
            onReady: [onReady, highlightWeek],
            onDestroy: onDestroy,
            onChange: function (selectedDates) {
                const sDate = fp.weekStartDay;
                const eDate = fp.weekEndDay;
                startDate =
                    `${sDate.getFullYear()}-${String(sDate.getMonth() + 1).padStart(2, '0')}-${String(sDate.getDate()).padStart(2, '0')}`;
                endDate =
                    `${eDate.getFullYear()}-${String(eDate.getMonth() + 1).padStart(2, '0')}-${String(eDate.getDate()).padStart(2, '0')}`;

                if (reinitialize_table) {
                    dataTable.destroy();
                    initTable(tableUrl, startDate, endDate);
                }
            },
        };
    };
}