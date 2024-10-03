@extends('layouts.app')

@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/employee/js/swalAlert.js') }}"></script>
@endsection
