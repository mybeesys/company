@extends('employee::layouts.master')

@section('title', __('employee::general.edit_employee'))
@section('content')
    <form id="edit_employee_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.update', ['employee' => $employee]) }}">
        @method('patch')
        @csrf
        <x-employee::employees.form :roles=$roles :employee=$employee :permissionSets=$permissionSets :allowances_types="$allowances_types"
            formId="edit_employee_form" :establishments=$establishments />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script>
        $(document).ready(function() {
            let saveButton = $(`#edit_employee_form_button`);
            datePicker('#employmentStartDate', new Date());
            datePicker('#employmentEndDate');
            permissionSetRepeater();
            allowanceRepeater("{{ route('allowance_types.store') }}", "{{ session()->get('locale') }}");
            roleRepeater();
            administrativeUser({{ $employee->administrativeUser()->exists() }});
            employeeForm('edit_employee_form', "{{ route('employees.update.validation') }}",
                "{{ route('employees.generate.pin') }}");
        });
    </script>
@endsection
