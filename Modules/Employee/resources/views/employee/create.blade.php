@extends('employee::layouts.master')

@section('title', __('employee::general.add_employee'))

@section('content')
    <form id="add_employee_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.store') }}">
        @csrf
        <x-employee::employees.form :roles=$roles :permissionSets=$permissionSets :establishments=$establishments formId="add_employee_form"/>
    </form>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script>
        $(document).ready(function() {
            datePicker('#employmentStartDate', new Date());
            permissionSetRepeater();
            roleRepeater();
            administrativeUser(false);
            employeeForm('add_employee_form', "{{ route('employees.create.validation') }}",
                "{{ route('employees.generate.pin') }}");
        });
    </script>
@endsection
