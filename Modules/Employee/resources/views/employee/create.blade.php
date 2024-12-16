@extends('employee::layouts.master')

@section('title', __('employee::general.add_employee'))

@section('content')
    <form id="add_employee_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.store') }}">
        @csrf
        <x-employee::employees.form :dashboardRoles=$dashboardRoles :posRoles=$posRoles :establishments=$establishments
            :allowances_types="$allowances_types" formId="add_employee_form" />
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script>
        $(document).ready(function() {
            let saveButton = $(`#add_employee_form_button`);
            datePicker('#employment_start_date', new Date());
            datePicker('#employment_end_date');
            permissionSetRepeater();
            roleRepeater();
            initElements();
            allowanceRepeater('allowance', "{{ route('adjustment_types.store') }}",
                "{{ session()->get('locale') }}");
            administrativeUser(false, 'add_employee_form');
            employeeForm('add_employee_form', "{{ route('employees.create.validation') }}",
                "{{ route('employees.generate.pin') }}");

            handleImageInput('imageInput');

        });
    </script>
@endsection
