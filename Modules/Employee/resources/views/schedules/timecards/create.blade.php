@extends('employee::layouts.master')

@section('title', __('employee::general.add_working_hours'))

@section('content')
    <form id="add_timecard_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        formId="add_timecard_form" action="{{ route('schedules.timecards.store') }}">
        @csrf
        <x-employee::timecards.form :employees=$employees :roles=$roles formId="add_timecard_form" />
    </form>
@endsection
@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-timecard.js') }}"></script>
    <script>
        let dataTable;
        $(document).ready(function() {
            const employeeSelect = $('[name="employee_id"]');
            const roleSelect = $('[name="role_id"]');

            employeeSelect.select2();
            roleSelect.select2({
                minimumResultsForSearch: -1,
            });
            const originalRoleOptions = roleSelect.html();

            function handleEmployeeChange() {
                let employeeId = employeeSelect.val();
                if (!employeeId) return;

                let url = `{{ route('employees.get-employee-roles', ':id') }}`.replace(':id', employeeId);
                roleSelect.prop('disabled', true);

                ajaxRequest(url, 'GET', {}, false, true, false).done(function(response) {
                    roleSelect.html(originalRoleOptions);
                    let rolesIds = response.data.map(String);

                    roleSelect.find('option').each(function() {
                        if (!rolesIds.includes($(this).val())) {
                            $(this).remove();
                        }
                    });
                    roleSelect.prop('disabled', false);
                });
            }
            employeeSelect.on('change', handleEmployeeChange);
            datePicker('#date');
            timecardForm("edit_timecard_form", "{{ route('schedules.timecards.create.validation') }}");
        });
    </script>
@endsection
