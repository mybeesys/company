@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="scheduleshift" :addButton=false :idColumn=false module="employee">
                <x-slot:filters>
                    <x-employee::schedules.filters />
                </x-slot:filters>
                <x-slot:elements>
                    <x-form.input-div class="mb-8 min-w-250px w-100" :row=false>
                        <x-form.input class="form-control form-control-solid" :label="__('employee::general.period')" name="periodDatePicker" />
                    </x-form.input-div>
                </x-slot:elements>
                <x-slot:export>
                    <x-tables.export-menu id="employee" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="scheduleshift" :actionColumn=false :idColumn=false module="employee" />
        </x-cards.card-body>
    </x-cards.card>

    @php
        $endStatusOptions = [
            ['id' => 'clockout', 'name' => __('employee::fields.clockout')],
            ['id' => 'break', 'name' => __('employee::fields.break')],
        ];
    @endphp
    <div class="modal fade" id="schedule_shift" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-1000px">
            <div class="modal-content">
                <div class="modal-header mb-2">
                    <h2 class="fw-bold">@lang('employee::general.shifts')</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body mx-5 pt-5">
                    <form id="schedule_shift_form" class="form" action="#">
                        @csrf
                        <div id="kt_modal_update_schedule_shift_scroll">
                            <div class="repeater-error-template d-none">
                                <div class="invalid-feedback repeater-error mb-5 mt-n2"></div>
                            </div>
                            <div id="error-container"></div>
                            <div id="schedule_shift_repeater">
                                <div class="form-group">
                                    <div class="d-flex align-items-center gap-3 px-2 mb-2">
                                        <label class="w-100px">@lang('employee::fields.start_time')</label>
                                        <span class="px-1"></span>
                                        <label class="w-100px">@lang('employee::fields.end_time')</label>
                                        <label style="width: 316.38px;">@lang('employee::fields.end_status')</label>
                                        <label style="width: 316.38px;" class="ps-2">@lang('employee::fields.role')</label>
                                        <label style="width: 34.83px;"></label>
                                    </div>
                                    <div data-repeater-list="schedule_shift_repeater" class="d-flex flex-column gap-3">
                                        <div data-repeater-item class="d-flex align-items-center gap-3">
                                            <x-form.input :errors="$errors" required :placeholder="__('employee::fields.h_m')"
                                                name="schedule_shift_repeater[][startTime]" readonly
                                                class="form-control-solid py-2 w-100px" />
                                            <span>-</span>
                                            <x-form.input :errors="$errors" required :placeholder="__('employee::fields.h_m')"
                                                name="schedule_shift_repeater[][endTime]" readonly
                                                class="form-control-solid py-2 w-100px" />
                                            <x-form.input-div class="w-100">
                                                <x-form.select name="schedule_shift_repeater[][end_status]" required
                                                    :options="$endStatusOptions" :errors="$errors" data_allow_clear="false" />
                                            </x-form.input-div>
                                            <x-form.input-div class="w-100">
                                                <x-form.select name="dashboard_role_repeater[][role]" required
                                                    data_allow_clear="false" :options=$roles :errors="$errors" />
                                            </x-form.input-div>
                                            <input type="hidden" name="schedule_shift_repeater[][shift_id]">
                                            <button type="button" data-repeater-delete
                                                class="btn btn-sm btn-icon btn-light-danger">
                                                <i class="ki-outline ki-cross fs-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-7">
                                    <button type="button" data-repeater-create class="btn btn-sm btn-light-primary">
                                        <i class="ki-outline ki-plus fs-2"></i>@lang('employee::general.add_more_shifts')</button>
                                </div>
                            </div>
                        </div>
                        <div class="text-center pt-5">
                            <button type="reset" class="btn btn-light me-3"
                                data-bs-dismiss="modal">@lang('general.cancel')</button>
                            <button type="submit" class="submit-form-btn btn btn-primary"
                                data-kt-schedule-shift-modal-action="submit">
                                <span class="indicator-label">@lang('general.save')</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script>
        let dataTable;
        let columns;
        let startDate;
        let endDate;
        let roleOptions = [];
        let roleValues = [];
        let employeeId;
        let date;

        $(document).ready(function() {
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

            let firstDayOfWeekNumber;
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
                "plugins": [weekSelectPlugin()],
                locale: {
                    firstDayOfWeek: firstDayOfWeekNumber
                },
                weekNumbers: true,
            });

            handleSearchDatatable();


            $('#schedule_shift_form').on('submit', function(e) {
                e.preventDefault();
                let data = $(this).serializeArray();
                data.push({
                    name: 'employee_id',
                    value: employeeId
                }, {
                    name: 'date',
                    value: date
                });
                ajaxRequest("{{ route('schedules.shift-schedules.store') }}", 'POST', data, true, true)
                    .done(function() {
                        dataTable.ajax.reload();
                        $('#schedule_shift').modal('toggle');
                    });
            })


            $(document).on('click', '.add-schedule-shift-button', function(e) {
                e.preventDefault();
                employeeId = $(this).data('employee-id');
                date = $(this).data('date');
                const data = $(this).data();
                const scheduleShiftIds = [];
                const startTimes = [];
                const endTimes = [];
                const roleId = [];
                const breakDuration = [];

                ajaxRequest("{{ url('/schedule/shift-schedule/get-shift-schedule') }}", 'GET', {
                    employee_id: employeeId,
                }, false, true).done(function(response) {
                    roleValues = Object.values(response.data).map(String);

                    $('select[name*="[role]"]').each(function() {
                        $(this).find('option').each(function() {
                            const optionValue = $(this).val();
                            if (!roleValues.includes(optionValue)) {
                                $(this).remove();
                            }
                        });
                    });

                    $('[data-repeater-create]').on('click', function() {
                        $('select[name*="[role]"]').each(function() {
                            $(this).find('option').each(function() {
                                const optionValue = $(this)
                                    .val();
                                if (!roleValues.includes(
                                        optionValue)) {
                                    $(this).remove();
                                }
                            });
                        });
                    });
                    setTimeout(() => {
                        $('#schedule_shift').modal('toggle');
                    }, 300);
                });

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

                const repeaterList = $('[data-repeater-list="schedule_shift_repeater"]');
                repeaterList.empty();
                scheduleShiftIds.forEach((shiftId, index) => {
                    $('[data-repeater-create]').trigger('click');
                    const newItem = repeaterList.find('[data-repeater-item]').last();
                    newItem.find('input[name*="[startTime]"]').val(startTimes[index] || '');
                    newItem.find('input[name*="[endTime]"]').val(endTimes[index] || '');
                    newItem.find('select[name*="[role]"]').val(roleId[index]).trigger('change');
                    newItem.find('input[name*="[shift_id]"]').val(shiftId);
                    if (breakDuration[index]) {
                        newItem.find('select[name*="[end_status]"]').val('break').trigger('change');
                    }
                });
            });

            $('#schedule_shift_repeater').repeater({
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

                    $(this).find('select[name^="schedule_shift_repeater"]').select2({
                        minimumResultsForSearch: -1,
                    });

                    $(this).find('select[name*="[end_status]"]').val('clockout').trigger('change');

                    $('#schedule_shift_repeater [data-repeater-item]').each(function() {
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
                            '#schedule_shift_repeater [data-repeater-item]').last();

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
                    if ($('#schedule_shift_repeater [data-repeater-item]').length > 1) {
                        $(this).slideUp(deleteElement);
                    } else {
                        showAlert(Lang.get('responses.emptyRepeaterwarning'),
                            Lang.get('general.ok'),
                            undefined, undefined,
                            false, "error");
                    }
                }
            });
        });

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
            $('#schedule_shift_repeater [data-repeater-item]').each(function() {
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
            }
        }


        function initTable(startDate, endDate) {
            updateTableHeader(startDate, endDate);
            dataTable = $('#kt_scheduleshift_table').DataTable({
                processing: true,
                serverSide: true,
                info: false,
                pageLength: 25,
                order: [],
                ajax: {
                    url: "{{ route('schedules.shift-schedules.index') }}",
                    data: function(d) {
                        d.start_date = startDate;
                        d.end_date = endDate;
                    },
                },
                columns: [{
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
                dom: "Btr <'row'<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start dt-toolbar'l><'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>>",
                buttons: [{
                    extend: 'colvis',
                    text: "{{ __('employee::general.column_visibility') }}",
                    className: 'mb-5 ms-3 ms-md-5 ms-xl-0',
                    columns: ':lt(6)'
                }, ]
            });
        }

        function updateTableHeader(startDate, endDate) {
            const $headerRow = $('#scheduleshift_headerRow');
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
            $headerRow.find('th:gt(5)').remove();

            while (currentDate.isSameOrBefore(endDate)) {
                const formattedDate = currentDate.format('MM/DD');
                const dayOfWeek = currentDate.format("dddd").toLowerCase();
                const dayTranslation = dayTranslations[dayOfWeek];

                const $th = $(`
                <th style="display: none;" class="min-w-200px border text-center py-1 align-middle">
                    <span class="d-flex flex-column">
                        <span>${dayTranslation}</span> 
                        <span>${formattedDate}</span>
                    </span>
                </th>
            `);
                columns.push({
                    data: currentDate.format('YYYY-MM-DD'),
                    name: currentDate.format('YYYY-MM-DD'),
                    className: 'text-start min-w-200px px-3 py-2 border text-center text-gray-800 fs-6'
                });
                $headerRow.append($th);
                $th.fadeIn(400);
                currentDate.add(1, 'day');
            }
            adjustTableRows($headerRow.find('th').length - 6); // Subtracting static columns  
        }

        function adjustTableRows(dateColumnsCount) {
            const $tableBody = $('#scheduleshift_tableBody');
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

        function weekSelectPlugin() {
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
                    initTable(startDate, endDate);
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
                        dataTable.destroy();
                        initTable(startDate, endDate);
                    },
                };
            };
        }
    </script>
@endsection
