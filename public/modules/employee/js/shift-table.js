function initTable(tableUrl, startDate, endDate) {
    updateTableHeader(startDate, endDate);
    dataTable = table.DataTable({
        processing: true,
        serverSide: true,
        info: false,
        pageLength: 25,
        order: [],
        ajax: {
            url: tableUrl,
            data: function (d) {
                d.start_date = startDate;
                d.end_date = endDate;
            },
        },
        columns: [{
            data: 'select',
            name: 'select',
            className: 'px-3 py-2 border',
            orderable: false,
            searchable: false
        },
        {
            data: 'id',
            name: 'id',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6',
        },
        {
            data: 'employee',
            name: 'employee',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6'
        },
        {
            data: 'total_hours',
            name: 'total_hours',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6'
        },
        {
            data: 'basic_wage',
            name: 'basic_wage',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6'
        },
        {
            data: 'total_wage',
            name: 'total_wage',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6'
        },
        {
            data: 'role',
            name: 'role',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6'
        },
        {
            data: 'establishment',
            name: 'establishment',
            className: 'text-start px-3 py-2 border text-gray-800 fs-6'
        },
        ...columns
        ],
        dom: "<'table-buttons'B> tr <'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start dt-toolbar'l><'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
        buttons: [{
            extend: 'colvis',
            text: Lang.get('general.column_visibility'),
            className: 'mb-5',
            columns: ':lt(6)'
        },
        {
            text: Lang.get('general.copy_shifts'),
            className: 'mb-5 copy-shifts-btn mw-275px',
            action: function (e, dt, node, config) {
                copyShifts();
            }
        }
        ],
        "footerCallback": tableFooter()
    });
    initToggleToolbar();
    addTableTypeButtons();
    $('.table-hours').on('click', function () {
        handleTableClick('hours', 'table-hours-footer');
    });

    $('.table-default').on('click', function () {
        handleTableClick('default', null);
    });

    $('.table-wage').on('click', function () {
        handleTableClick('wage', 'table-wages-footer');
    });

    $('.table-breaks').on('click', function () {
        handleTableClick('breaks', 'table-breaks-footer');
    });
}

function handleTableClick(type, activeFooterClass) {
    $('.nav-link.active').removeClass('active');
    $(`.table-${type}`).addClass('active');

    tableType = type;

    $('.table-wages-footer, .table-hours-footer, .table-breaks-footer').addClass('d-none');
    if (activeFooterClass) {
        $(`.${activeFooterClass}`).removeClass('d-none');
    }
    dataTable.ajax.url(tableUrl + '?' + $.param({
        table_type: tableType,
        filter_role: filterRoleId,
        filter_establishment: filterEstablishmentId,
        filter_employee_status: filterEmployeeStatus,
        format: filterFormat
    })).load();
}

function addTableTypeButtons() {
    $('.table-buttons').addClass('d-flex justify-content-between flex-wrap').append(
        `<div class="card-rounded bg-light d-flex mb-5 table-type-buttons">		
                <ul class="nav d-flex flex-nowrap border-transparent fw-bold">
                    <li class="nav-item">
                        <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 active table-default" href="#">${Lang.get('general.shifts')}</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 table-hours" href="#">${Lang.get('fields.hours')}</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 table-breaks" href="#">${Lang.get('fields.breaks')}</a>
                    </li>
                </ul>
        </div>`
    );
}

function updateTableHeader(startDate, endDate) {
    const $headerRow = $('#shift_headerRow');
    columns = [];
    const dayTranslations = {
        'sunday': Lang.get('general.sunday'),
        'monday': Lang.get('general.monday'),
        'tuesday': Lang.get('general.tuesday'),
        'wednesday': Lang.get('general.wednesday'),
        'thursday': Lang.get('general.thursday'),
        'friday': Lang.get('general.friday'),
        'saturday': Lang.get('general.saturday')
    };

    let currentDate = moment(startDate, 'YYYY-MM-DD');
    const endMomentDate = moment(endDate, 'YYYY-MM-DD');
    // Remove all date columns immediately
    $headerRow.find('th:gt(6)').remove();

    while (currentDate.isSameOrBefore(endDate)) {
        const formattedDate = currentDate.format('MM/DD');
        const dayOfWeek = currentDate.format("dddd").toLowerCase();
        const dayTranslation = dayTranslations[dayOfWeek];

        const $th = $(`
        <th style="display: none;" class="min-w-125px border text-center py-1 align-middle">
            <span class="d-flex flex-column">
                <span>${dayTranslation}</span> 
                <span>${formattedDate}</span>
            </span>
        </th>`);
        columns.push({
            data: currentDate.format('YYYY-MM-DD'),
            name: currentDate.format('YYYY-MM-DD'),
            className: 'text-start min-w-125px px-3 py-2 border text-center text-gray-800 fs-6'
        });
        $headerRow.append($th);
        $th.fadeIn(400);
        currentDate.add(1, 'day');
    }
}


function initToggleToolbar() {
    selectedCount = $('[data-kt-shift-table-select="selected_count"]');

    $('#kt_shift_table_wrapper').on('click', '.shift_select', function () {
        setTimeout(function () {
            toggleToolbars();
        }, 50);
    });
}

function toggleToolbars() {
    const allCheckboxes = table.find('tbody [type="checkbox"]');
    let checkedState = false;
    let count = 0;

    allCheckboxes.each(function () {
        if (this.checked) {
            checkedState = true;
            count++;
        }
    });
    if (checkedState) {
        selectedCount.html(' ' + count);
        $('.copy-shifts-btn').html(Lang.get('general.copy_selected_employees_shifts'));
    } else {
        $('.copy-shifts-btn').html(Lang.get('general.copy_shifts'));
    }
}

function tableFooter() {
    return function (row, data, start, end, display) {
        var api = this.api();

        var columnsToSum = [1];
        for (var i = 5; i <= 11; i++) {
            columnsToSum.push(i);
        }

        let format = $('select[name="format_filter"]').val();

        $(api.table().footer()).find('tr').each(function () {
            var row = $(this);

            columnsToSum.forEach(function (colIndex) {
                var footerCell = row.find('span').eq(colIndex);
                if (row.hasClass('table-hours-footer') && $('.table-type-buttons .table-hours')
                    .hasClass('active')) {
                    tableHoursFooter(api, colIndex, format, footerCell);
                } else if (row.hasClass('table-wages-footer')) {

                } else if (row.hasClass('table-breaks-footer') && $(
                    '.table-type-buttons .table-breaks').hasClass('active')) {
                    tableBreaksFooter(api, colIndex, format, footerCell);
                }
            });
        });
    }
}

var intVal = function (i) {
    if (typeof i === "string" && i.match(/(\d{2}):(\d{2})/)) {
        var timeParts = i.split(':');
        var hours = parseFloat(timeParts[0]);
        var minutes = parseFloat(timeParts[1]);
        return (hours * 60 + minutes) / 60;
    } else {
        var x = parseFloat(i, 10);
        return isNaN(x) ? 0 : x;
    }
};

function tableHoursFooter(api, colIndex, format, footerCell) {
    var total = api.column(colIndex + 2).data().reduce(function (a, b) {
        var value = $(b).text() || b;

        return intVal(a) + intVal(value);
    }, 0).toFixed(2);

    var pageTotal = api.column(colIndex + 2, {
        page: "current"
    }).data().reduce(function (a, b) {
        var value = $(b).text() || b;

        return intVal(a) + intVal(value);
    }, 0).toFixed(2);

    if (format === 'decimal') {
        footerCell.html(pageTotal + " (" + total + ")");
    } else {
        var convertToHHMM = function (value) {
            var hours = Math.floor(value); // Get whole hours
            var minutes = Math.round((value - hours) * 60);
            minutes = minutes < 10 ? '0' + minutes : minutes;

            return hours + ':' + minutes; // Return in HH:MM format
        };
        var formattedTotal = convertToHHMM(total);
        var formattedPageTotal = convertToHHMM(pageTotal);

        footerCell.html(formattedPageTotal + " (" + formattedTotal + ")");
    }
}

function tableBreaksFooter(api, colIndex, format, footerCell) {
    function extractTimeData(b) {
        var matches = $(b).find('div').map(function () {
            var text = $(this).text().trim();
            var timeMatch = text.match(/\((\d{2}):(\d{2})\)/);
            if (timeMatch) {
                var hours = parseFloat(timeMatch[1], 10);
                var minutes = parseFloat(timeMatch[2], 10);
                return (hours * 60) + minutes;
            }
            var numberMatch = text.match(/\((\d+(\.\d+)?)\)/);
            if (numberMatch) {
                return parseFloat(numberMatch[1], 10);
            }
            return 0;
        }).get();

        return matches.reduce(function (total, num) {
            return total + num;
        }, 0);
    }

    function convertToHHMM(value) {
        var hours = Math.floor(value);
        var minutes = Math.round((value - hours) * 60);
        minutes = minutes < 10 ? '0' + minutes : minutes;
        return hours + ':' + minutes;
    }

    var total = api.column(colIndex + 2).data().reduce(function (a, b) {
        return a + extractTimeData(b);
    }, 0);

    var pageTotal = api.column(colIndex + 2, {
        page: 'current'
    }).data().reduce(function (a, b) {
        return a + extractTimeData(b);
    }, 0);

    if (format === 'decimal') {
        footerCell.html(pageTotal + " (" + total + ")");
    } else {
        var formattedTotal = convertToHHMM(total);
        var formattedPageTotal = convertToHHMM(pageTotal);

        footerCell.html(formattedPageTotal + " (" + formattedTotal + ")");
    }
}