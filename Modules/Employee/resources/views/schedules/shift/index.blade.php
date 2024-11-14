@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="shift" :addButton=false :idColumn=false module="employee">
                <x-slot:filters>
                    <x-employee::schedules.filters :establishments=$establishments :roles=$roles />
                </x-slot:filters>
                <x-slot:elements>
                    <x-form.input-div class="mb-md-8 min-w-200px w-100" :row=false>
                        <x-form.input class="form-control form-control-solid" :label="__('employee::general.period')" name="periodDatePicker" />
                    </x-form.input-div>
                </x-slot:elements>
                <x-slot:export>
                    <x-tables.export-menu id="shift" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="shift" :actionColumn=false :idColumn=false selectColumn
                :footers=$footers module="employee" />
        </x-cards.card-body>
    </x-cards.card>

    <x-employee::schedules.add-shift-modal />
    <x-employee::schedules.copy-shift-modal />
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>

    <script>
        let dataTable;
        let columns;
        let roleValues = [];
        let employeeId;
        let date;
        let firstDayOfWeekNumber;
        let filterRoleId;
        let filterEstablishmentId;
        let filterEmployeeStatus;
        let filterFormat;
        let copyShiftflatpickrInstance;
        let tableType;

        const table = $('#kt_shift_table');

        $(document).ready(function() {
            initElements();
            addShiftForm();
            addShiftModal();
            shiftRepeater();
            exportButtons([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], '#kt_shift_table',
                "{{ session()->get('locale') }}", [5], [11], 'A2');
            handleFilters();

            $('.table-buttons').addClass('d-flex justify-content-between flex-wrap').append(
                `<div class="card-rounded bg-light d-flex mb-5">		
                        <ul class="nav d-flex flex-nowrap border-transparent fw-bold">
                            <li class="nav-item">
                                <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 active table-shift" href="#">{{ __('employee::general.shifts') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 table-hours" href="#">{{ __('employee::fields.hours') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 table-wage" href="#">{{ __('employee::fields.wage-forecasting') }}</a>
                            </li>
                            <li class="nav-item">
                                <a class="btn btn-color-gray-800 btn-active-secondary btn-active-color-primary text-nowrap fs-6 nav-link px-6 table-break" href="#">{{ __('employee::fields.breaks') }}</a>
                            </li>
                        </ul>
                </div>`
            )
            $('.table-hours').on('click', function() {
                $('.nav-link.active').removeClass('active');
                $(this).addClass('active');
                tableType = 'hours';

                dataTable.ajax.url('{{ route('schedules.shifts.index') }}?' + $.param({
                    table_type: tableType,
                    filter_role: filterRoleId,
                    filter_establishment: filterEstablishmentId,
                    filter_employee_status: filterEmployeeStatus,
                    format: filterFormat
                })).load();

                $('.table-hours-footer').removeClass('d-none');
            });

            $('.table-shift').on('click', function() {
                $('.nav-link.active').removeClass('active');
                $(this).addClass('active');
                tableType = 'default';
                dataTable.ajax.url('{{ route('schedules.shifts.index') }}?' + $.param({
                    table_type: tableType,
                    filter_role: filterRoleId,
                    filter_establishment: filterEstablishmentId,
                    filter_employee_status: filterEmployeeStatus,
                    format: filterFormat
                })).load();

                $('.table-hours-footer').addClass('d-none');
            })

            $('#copyShiftDatePicker').on('click', function() {
                copyShiftflatpickrInstance.open();
            });

            $('#shift_copy_form').on('submit', function(e) {
                e.preventDefault();

                const checkedCheckboxes = table.find('tbody [type="checkbox"]:checked');
                let employeeIds = [];
                checkedCheckboxes.each(function() {
                    employeeIds.push($(this).data('employee-id'));
                });

                let copyFromDate = $('#periodDatePicker').val().split('-');
                let copyDate = $('#copyShiftDatePicker').val().split('-');
                let data = {
                    start_date: copyDate[0],
                    end_date: copyDate[1],
                    copy_from_start_date: copyFromDate[0],
                    copy_from_end_date: copyFromDate[1],
                    employee_ids: employeeIds
                };
                ajaxRequest("{{ route('schedules.shifts.copy-shifts') }}", 'POST', data, true, true)
                    .done(function() {
                        dataTable.ajax.reload();
                        $('#shift_copy').modal('toggle');
                    }).always(function() {
                        $('.copy-shifts-btn').html("{{ __('employee::general.copy_shifts') }}");
                    });
            });
        });

        function addShiftForm() {
            $('#shift_add_form').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serializeArray();
                data.push({
                    name: 'employee_id',
                    value: employeeId
                }, {
                    name: 'date',
                    value: date
                });
                ajaxRequest("{{ route('schedules.shifts.store') }}", 'POST', data, true, true)
                    .done(function() {
                        dataTable.ajax.reload();
                        $('#shift_add').modal('toggle');
                    });
            });
        }

        function handleFilters() {
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
                dataTable.ajax.url('{{ route('schedules.shifts.index') }}?' + $.param({
                    table_type: tableType,
                    filter_role: filterRoleId,
                    filter_establishment: filterEstablishmentId,
                    filter_employee_status: filterEmployeeStatus,
                    format: filterFormat
                })).load();
            }
        }

        function addShiftModal() {
            $(document).on('click', '.add-schedule-shift-button', function(e) {
                e.preventDefault();
                employeeId = $(this).data('employee-id');
                const employeeName = $(this).data('employee-name');

                date = $(this).data('date');
                const data = $(this).data();
                const scheduleShiftIds = [];
                const startTimes = [];
                const endTimes = [];
                const roleId = [];
                const breakDuration = [];

                ajaxRequest("{{ url('/schedule/shift/get-shift-schedule') }}", 'GET', {
                    employee_id: employeeId,
                }, false, true).done(function(response) {
                    roleValues = response.data.roles;

                    let startDayTime = response.data.start_of_day;
                    let endDayTime = response.data.end_of_day;

                    if (startDayTime && endDayTime) {
                        $('.work-time-hint').html(
                            `{{ __('employee::general.day_times_hint', ['start' => ':start', 'end' => ':end']) }}`
                            .replace(':start', startDayTime)
                            .replace(':end', endDayTime)
                        );
                    }

                    $('[data-repeater-create]').on('click', function() {
                        const newRow = $('select[name*="[role]"]').last();

                        $.each(roleValues, function(roleName, roleId) {
                            if (newRow.find(`option[value="${roleId}"]`).length ===
                                0) {
                                newRow.append(new Option(roleName, roleId));
                            }
                        });
                    });

                    $('.work-time-modal-title').html(employeeName + ' | ' + date);

                    for (const key in data) {
                        if (key.startsWith('scheduleShiftId')) {
                            scheduleShiftIds.push(data[key]);
                        } else if (key.startsWith('startTime')) {
                            startTimes.push(data[key]);
                        } else if (key.startsWith('endTime')) {
                            endTimes.push(data[key]);
                        } else if (key.startsWith('roleId')) {
                            roleId.push(data[key]);
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
                        newItem.find('select[name*="[role]"]').val(roleId[index]).trigger('change');
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

        function shiftRepeater() {
            $('#shift_repeater').repeater({
                show: function() {
                    $(this).slideDown();
                    let startTime;
                    let endTime;

                    const startTimeInput = $(this).find('input[name*="[startTime]"]');
                    const endTimeInput = $(this).find('input[name*="[endTime]"]');
                    timePicker(startTimeInput[0]);
                    timePicker(endTimeInput[0]);

                    startTimeInput.on('change.td', function(
                        e) {
                        if (e.date) {
                            endTime = endTimeInput.val();
                            startTime = e.date.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            });
                            startEndTimeValidate(startTime, endTime, $(this), endTimeInput);
                        }
                    });

                    endTimeInput.on('change.td', function(e) {
                        if (e.date) {
                            startTime = startTimeInput.val()
                            endTime = e.date.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            });
                            startEndTimeValidate(startTime, endTime, $(this), startTimeInput);
                        }
                    });

                    $(this).find('select[name^="shift_repeater"]').select2({
                        minimumResultsForSearch: -1,
                    });

                    $(this).find('select[name*="[end_status]"]').val('clockout').trigger('change');

                    $('#shift_repeater [data-repeater-item]').each(function() {
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

                    $(this).find('select[name*="[end_status]"]').on('change', function() {
                        const name = $(this).attr('name');
                        const breakErrorId = name.replace(/\[\d+\]\[\w+\]/, (match) => {
                            const index = match.match(/\[(\d+)\]/)[1];
                            return `[${index}][break]`;
                        });

                        const lastRepeaterItem = $(
                            '#shift_repeater [data-repeater-item]').last();

                        if ($(this).val() === 'break') {
                            if ($(this).closest('[data-repeater-item]').is(lastRepeaterItem)) {
                                addErrorMessage(
                                    "{{ __('employee::general.last_element_end_status_error') }}",
                                    breakErrorId
                                );
                                checkErrors($('.submit-form-btn'));
                            }
                        } else {
                            $(`.repeater-error[data-error-id="${breakErrorId}"]`).remove();
                            checkErrors($('.submit-form-btn'));
                        }
                    });
                },
                hide: function(deleteElement) {
                    if ($('#shift_repeater [data-repeater-item]').length > 1) {
                        $(this).slideUp(deleteElement);
                    } else {
                        showAlert(Lang.get('responses.emptyRepeaterwarning'),
                            Lang.get('general.ok'),
                            undefined, undefined,
                            false, "error");
                    }
                }
            });
        }

        function initElements() {
            moment.updateLocale('en', {
                week: {
                    dow: 6
                }
            });
            var start = moment().startOf('week');
            var end = moment().endOf('week');
            let firstDayOfWeek =
                "{{ $timeSheet_rules->firstWhere('rule_name', '=', 'weak_starts_on')?->rule_value }}";
            if (!firstDayOfWeek) {
                firstDayOfWeek = 'saturday';
            }

            switch (firstDayOfWeek) {
                case "sunday":
                    firstDayOfWeekNumber = 0;
                    break;
                case "monday":
                    firstDayOfWeekNumber = 1;
                    break;
                case "tuesday":
                    firstDayOfWeekNumber = 2;
                    break;
                case "wednesday":
                    firstDayOfWeekNumber = 3;
                    break;
                case "thursday":
                    firstDayOfWeekNumber = 4;
                    break;
                case "friday":
                    firstDayOfWeekNumber = 5;
                    break;
                case "saturday":
                    firstDayOfWeekNumber = 6;
            }

            $("#periodDatePicker").flatpickr({
                "plugins": [weekSelectPlugin(true)],
                locale: {
                    firstDayOfWeek: firstDayOfWeekNumber
                },
                weekNumbers: true,
            });

            $('[name="establishment_filter"]').select2({
                minimumResultsForSearch: -1,
            });

            $('[name="format_filter"]').select2({
                minimumResultsForSearch: -1,
            });

            $('[name="role_filter"]').select2({
                minimumResultsForSearch: -1,
            });
            $('[name="employee_filter"]').select2({
                minimumResultsForSearch: -1,
            });

            handleSearchDatatable();
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
                    addErrorMessage(
                        "{{ __('employee::general.startTime_before_endTime_error') }}",
                        errorId);
                } else {
                    thisElement.removeClass('is-invalid');
                    otherInput.removeClass('is-invalid');
                    $(`.repeater-error[data-error-id="${errorId}"]`).remove();
                    checkErrors($('.submit-form-btn'));
                }
            }

            let hasOverlap = false;
            $('#shift_repeater [data-repeater-item]').each(function() {
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
                addErrorMessage(
                    "{{ __('employee::general.time_overlap_error') }}",
                    conflictErrorId
                );
            } else {
                $(`.repeater-error[data-error-id="${conflictErrorId}"]`).remove();
                checkErrors($('.submit-form-btn'));
            }
        }

        function initTable(startDate, endDate) {
            updateTableHeader(startDate, endDate);
            dataTable = table.DataTable({
                processing: true,
                serverSide: true,
                info: false,
                pageLength: 25,
                order: [],
                ajax: {
                    url: "{{ route('schedules.shifts.index') }}",
                    data: function(d) {
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
                        data: 'total_wages',
                        name: 'total_wages',
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
                        text: "{{ __('employee::general.column_visibility') }}",
                        className: 'mb-5',
                        columns: ':lt(6)'
                    },
                    {
                        text: "{{ __('employee::general.copy_shifts') }}",
                        className: 'mb-5 copy-shifts-btn mw-275px',
                        action: function(e, dt, node, config) {
                            copyShifts();
                        }
                    }
                ],
                "footerCallback": function(row, data, start, end, display) {
                    var api = this.api();

                    var intVal = function(i) {
                        if (typeof i === "string" && i.match(/(\d{2}):(\d{2})/)) {
                            var timeParts = i.split(':');
                            var hours = parseInt(timeParts[0]);
                            var minutes = parseInt(timeParts[1]);
                            return (hours * 60 + minutes) / 60;
                        } else {
                            var x = parseFloat(i, 10);
                            return isNaN(x) ? 0 : x;
                        }
                    };

                    var columnsToSum = [3];
                    for (var i = 7; i <= 13; i++) {
                        columnsToSum.push(i);
                    }

                    let format = $('select[name="format_filter"]').val();
                    if (!$('.table-shift').hasClass('active')) {
                        columnsToSum.forEach(function(colIndex) {
                            var total = api.column(colIndex).data().reduce(function(a, b) {
                                var value = $(b).find('div').text() || b;
                                return intVal(a) + intVal(value);
                            }, 0).toFixed(2);

                            var pageTotal = api.column(colIndex, {
                                page: "current"
                            }).data().reduce(function(a, b) {
                                var value = $(b).find('div').text() || b;
                                return intVal(a) + intVal(value);
                            }, 0).toFixed(2);
                            if (format === 'decimal') {
                                $(api.column(colIndex).footer()).html(pageTotal + " (" + total + ")");
                            } else {
                                var convertToHHMM = function(value) {
                                    var hours = Math.floor(value); // Get whole hours
                                    var minutes = Math.round((value - hours) * 60);
                                    minutes = minutes < 10 ? '0' + minutes : minutes;

                                    return hours + ':' + minutes; // Return in HH:MM format
                                };
                                var formattedTotal = convertToHHMM(total);
                                var formattedPageTotal = convertToHHMM(pageTotal);

                                $(api.column(colIndex).footer()).html(formattedPageTotal + " (" +
                                    formattedTotal + ")");
                            }
                        });
                    }
                }
            });
            initToggleToolbar();
        }

        function copyShifts() {
            if (copyShiftflatpickrInstance) {
                copyShiftflatpickrInstance.destroy();
            }
            copyShiftflatpickrInstance = $("#copyShiftDatePicker").flatpickr({
                plugins: [weekSelectPlugin(false)],
                locale: {
                    firstDayOfWeek: firstDayOfWeekNumber
                },
                weekNumbers: true,
                clickOpens: false
            });

            let copyDate = $('#copyShiftDatePicker').val().split('-');

            if ($('#copyShiftDatePicker').val()) {
                $('.copy-shifts-modal-title').html(
                    "{{ __('employee::general.copy_warning', ['start' => ':start', 'end' => ':end']) }}".replace(
                        ':start', copyDate[0]).replace(':end', copyDate[1]));
            }
            $('#shift_copy').modal('toggle');

            $('#copyShiftDatePicker').on('change', function() {
                copyDate = $(this).val().split('-');
                $('.copy-shifts-modal-title').html(
                    "{{ __('employee::general.copy_warning', ['start' => ':start', 'end' => ':end']) }}"
                    .replace(
                        ':start', copyDate[0]).replace(':end', copyDate[1]));
            });
        }

        function updateTableHeader(startDate, endDate) {
            const $headerRow = $('#shift_headerRow');
            columns = [];
            const dayTranslations = {
                'sunday': '{{ __('employee::general.sunday') }}',
                'monday': '{{ __('employee::general.monday') }}',
                'tuesday': '{{ __('employee::general.tuesday') }}',
                'wednesday': '{{ __('employee::general.wednesday') }}',
                'thursday': '{{ __('employee::general.thursday') }}',
                'friday': '{{ __('employee::general.friday') }}',
                'saturday': '{{ __('employee::general.saturday') }}'
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
            adjustTableRows($headerRow.find('th').length - 7); // Subtracting static columns  
        }

        function adjustTableRows(dateColumnsCount) {
            const $tableBody = $('#shift_tableBody');
            $tableBody.find('tr').each(function() {
                $(this).find('td').remove();
            });
        }

        function addErrorMessage(message, errorId) {
            if ($(`.repeater-error[data-error-id="${errorId}"]`).length === 0) {
                const errorClone = $('.repeater-error-template .repeater-error').clone();
                errorClone.html(message).css('display', 'block').addClass('is-invalid').attr('data-error-id', errorId);
                $('#error-container').append(errorClone);
                checkErrors($('.submit-form-btn'));
            }
        }

        function weekSelectPlugin(reinitialize_table) {
            let startDate;
            let endDate;
            return function(fp) {
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
                        initTable(startDate, endDate);
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
                    onParseConfig: function() {
                        fp.config.mode = "single";
                        fp.config.enableTime = false;
                        fp.config.dateFormat = "d/m/Y - d/m/Y";
                        fp.config.altFormat = "d/m/Y - d/m/Y";
                    },
                    onReady: [onReady, highlightWeek],
                    onDestroy: onDestroy,
                    onChange: function(selectedDates) {
                        const sDate = fp.weekStartDay;
                        const eDate = fp.weekEndDay;
                        startDate =
                            `${sDate.getFullYear()}-${String(sDate.getMonth() + 1).padStart(2, '0')}-${String(sDate.getDate()).padStart(2, '0')}`;
                        endDate =
                            `${eDate.getFullYear()}-${String(eDate.getMonth() + 1).padStart(2, '0')}-${String(eDate.getDate()).padStart(2, '0')}`;

                        if (reinitialize_table) {
                            dataTable.destroy();
                            initTable(startDate, endDate);
                        }
                    },
                };
            };
        }

        function initToggleToolbar() {
            selectedCount = $('[data-kt-shift-table-select="selected_count"]');

            $('#kt_shift_table_wrapper').on('click', '.shift_select', function() {
                setTimeout(function() {
                    toggleToolbars();
                }, 50);
            });
        }

        function toggleToolbars() {
            const allCheckboxes = table.find('tbody [type="checkbox"]');
            let checkedState = false;
            let count = 0;

            allCheckboxes.each(function() {
                if (this.checked) {
                    checkedState = true;
                    count++;
                }
            });
            if (checkedState) {
                selectedCount.html(' ' + count);
                $('.copy-shifts-btn').html("{{ __('employee::general.copy_selected_employees_shifts') }}");
            } else {
                $('.copy-shifts-btn').html("{{ __('employee::general.copy_shifts') }}");
            }
        }
    </script>
@endsection
