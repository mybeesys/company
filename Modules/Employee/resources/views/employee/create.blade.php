@extends('employee::layouts.master')

@section('title', __('employee::general.add_employee'))

@section('content')
    <form id="add_employee_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.store') }}">
        @csrf
        <x-employee::employees.form :roles=$roles :permissionSets=$permissionSets :establishments=$establishments />
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            datePicker();
            roleRepeater();
            administrativeUser(false);
            employeeForm('add_employee_form', "{{ route('employees.create.validation') }}",
                "{{ route('employees.generate.pin') }}");
        });
    </script>
@endsection
