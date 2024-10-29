@extends('employee::layouts.master')

@section('title', __('employee::general.add_working_hours'))

@section('content')
    <form id="add_timecard_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        formId="add_timecard_form" action="{{ route('timecards.store') }}">
        @csrf
        <x-employee::timecards.form :employees=$employees formId="add_timecard_form" />
    </form>
@endsection
@section('script')
    @parent
    <script src="{{ url('modules/employee/js/create-edit-timecard.js') }}"></script>
    <script>
        let dataTable;
        $(document).ready(function() {
            $('[name="employee_id"]').select2();
            datePicker('#date');
            timecardForm("add_timecard_form", "{{ route('timecards.create.validation') }}");
        });
    </script>
@endsection
