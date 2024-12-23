@extends('layouts.app')
@section('css')
    <style>
        @if (session('locale') == 'ar')
            input[type="number"] {
                text-align: right;
            }

            input[type="number"]::-webkit-input-placeholder,
            input[type="email"]::-webkit-input-placeholder {
                text-align: right;
            }
        @endif
    </style>
    <link rel="stylesheet" href="{{ url('css/monthSelectPlugin.css') }}">
@endsection
@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>
    <script src="{{ url('js/monthSelectPlugin.js') }}"></script>
@endsection