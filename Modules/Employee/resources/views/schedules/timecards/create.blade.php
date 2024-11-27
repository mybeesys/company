@extends('employee::layouts.master')

@section('title', __('employee::general.add_working_hours'))

@section('content')
    <form id="add_timecard_form" class="form d-flex flex-column flex-lg-row" method="POST"
        formId="add_timecard_form" action="{{ route('schedules.timecards.store') }}">
        @csrf
        <x-employee::timecards.form :employees=$employees :establishments=$establishments formId="add_timecard_form" />
    </form>
@endsection
@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-timecard.js') }}"></script>
    <script>
        let dataTable;
        $(document).ready(function() {
            const employeeSelect = $('[name="employee_id"]');
            const establishmentSelect = $('[name="establishment_id"]');

            employeeSelect.select2();
            establishmentSelect.select2({
                minimumResultsForSearch: -1,
            });
            const originalRoleOptions = establishmentSelect.html();

            function handleEmployeeChange() {
                let employeeId = employeeSelect.val();
                if (!employeeId) return;

                let url = `{{ route('employees.get-employee-establishments', ':id') }}`.replace(':id', employeeId);
                establishmentSelect.prop('disabled', true);

                ajaxRequest(url, 'GET', {}, false, true, false).done(function(response) {
                    establishmentSelect.html(originalRoleOptions);
                    let establishmentsIds = response.data.map(String);

                    establishmentSelect.find('option').each(function() {
                        if (!establishmentsIds.includes($(this).val())) {
                            $(this).remove();
                        }
                    });
                    establishmentSelect.prop('disabled', false);
                });
            }
            employeeSelect.on('change', handleEmployeeChange);
            datePicker('#date');
            timecardForm("edit_timecard_form", "{{ route('schedules.timecards.create.validation') }}", "{{ $maximum_regular_hours }}", "{{ $maximum_overtime_hours }}");
        });
    </script>
@endsection
