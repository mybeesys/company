@extends('employee::layouts.master')

@section('title', __('employee::general.edit_employee'))
@section('content')
    <form id="edit_employee_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.update', ['employee' => $employee]) }}">
        @method('put')
        @csrf
        <x-employee::employees.form :employee=$employee />
    </form>
@endsection

@section('script')
    @parent
    <script>
        datePicker();
        form('edit_employee_form', "{{ route('employees.update.validation') }}",
        "{{ route('employees.generate.pin') }}")
    </script>
@endsection
