@extends('employee::layouts.master')

@section('title', __('employee::general.edit_working_hours'))

@section('content')
    <form id="edit_timecard_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        formId="edit_timecard_form" action="{{ route('schedules.timecards.update', ['timecard' => $timecard]) }}">
        @csrf
        @method('patch')
        <x-employee::timecards.form :employees=$employees :timecard=$timecard formId="edit_timecard_form" />
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
            timecardForm("edit_timecard_form", "{{ route('schedules.timecards.create.validation') }}");
        });
    </script>
@endsection
