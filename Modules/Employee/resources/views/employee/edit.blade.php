@extends('employee::layouts.master')

@section('title', __('employee::general.edit_employee'))
@section('content')
    <form id="edit_employee_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.update', ['employee' => $employee]) }}">
        @method('patch')
        @csrf
        <x-employee::employees.form :roles=$roles :employee=$employee :permissionSets=$permissionSets
            :establishments=$establishments />
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            datePicker();
            permissionSetRepeater();
            roleRepeater();
            administrativeUser({{ $employee->administrativeUser()->exists() ? true : false }});
            employeeForm('edit_employee_form', "{{ route('employees.update.validation') }}",
                "{{ route('employees.generate.pin') }}");
        });
    </script>
@endsection
