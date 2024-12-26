@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="shift" :addButton=false module="employee">
                <x-slot:filters>
                    <x-employee::shifts.filters :establishments=$establishments :roles=$roles />
                </x-slot:filters>
                <x-slot:elements>
                    <x-form.input-div class="mb-md-8 min-w-200px w-100" :row=false>
                        <x-form.input class="form-control-solid" :label="__('employee::general.period')" name="periodDatePicker" />
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

    <x-employee::shifts.add-shift-modal />
    <x-employee::shifts.copy-shift-modal />
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <script src="{{ url('modules/employee/js/shift.js') }}"></script>
    <script src="{{ url('modules/employee/js/shift-modal.js') }}"></script>
    <script src="{{ url('modules/employee/js/shift-table.js') }}"></script>

    <script>
        let dataTable;
        let columns;
        let establishmentsValues = [];
        let employeeId;
        let date;
        let firstDayOfWeekNumber;
        let filterRoleId;
        let filterEstablishmentId;
        let filterEmployeeStatus;
        let filterFormat;
        let copyShiftFlatpickrInstance;
        let tableType;
        const tableUrl = '{{ route('schedules.shifts.index') }}'
        const table = $('#kt_shift_table');

        $(document).ready(function() {
            initElements();
            addShiftForm("{{ route('schedules.shifts.store') }}");
            addShiftModal("{{ url('/schedule/shift/get-shift') }}");
            shiftRepeater();
            exportButtons([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], '#kt_shift_table',
                "{{ session('locale') }}", [5], [11], 'A2');
            handleFilters(tableUrl);


            $('#copyShiftDatePicker').on('click', function() {
                copyShiftFlatpickrInstance.open();
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

        function initElements() {
            moment.updateLocale('en', {
                week: {
                    dow: 6
                }
            });
            var start = moment().startOf('week');
            var end = moment().endOf('week');
            let firstDayOfWeek =
                "{{ $timeSheet_rules->firstWhere('rule_name', '=', 'week_starts_on')?->rule_value }}";
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

        function addErrorMessage(message, errorId) {
            if ($(`.repeater-error[data-error-id="${errorId}"]`).length === 0) {
                const errorClone = $('.repeater-error-template .repeater-error').clone();
                errorClone.html(message).css('display', 'block').addClass('is-invalid').attr('data-error-id', errorId);
                $('#error-container').append(errorClone);
                checkErrors($('.submit-form-btn'));
            }
        }
    </script>
@endsection
