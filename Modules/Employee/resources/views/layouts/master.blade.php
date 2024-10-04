@extends('layouts.app')

@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/employee/js/swalAlert.js') }}"></script>
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>
    <script src="{{ url('modules/employee/js/create-edit-employee.js') }}"></script>
    <script src="{{ url('modules/employee/js/date-picker.js') }}"></script>
    <script>
        window.csrfToken = '{{ csrf_token() }}';
    </script>
@endsection
