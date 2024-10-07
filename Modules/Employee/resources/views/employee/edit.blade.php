@extends('employee::layouts.master')

@section('title', __('employee::general.edit_employee'))
@section('content')
    <form id="edit_employee_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.update', ['employee' => $employee]) }}">
        @method('patch')
        @csrf
        <x-employee::employees.form :roles=$roles :employee=$employee />
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            datePicker();
            employeeForm('edit_employee_form', "{{ route('employees.update.validation') }}",
                "{{ route('employees.generate.pin') }}")
        });
    </script>
@endsection
