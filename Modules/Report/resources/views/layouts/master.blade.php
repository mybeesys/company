@extends('layouts.app')
@section('css')
    <link rel="stylesheet" href="{{ url('css/monthSelectPlugin.css') }}">
@endsection
@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>
    <script src="{{ url('js/monthSelectPlugin.js') }}"></script>
@endsection
