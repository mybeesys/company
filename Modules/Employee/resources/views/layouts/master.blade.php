@extends('layouts.app')
@section('css')
    <style>
        @if (session()->get('locale') == 'ar')
            input[type="number"] {
                text-align: right;
            }

            input[type="number"]::-webkit-input-placeholder,
            input[type="email"]::-webkit-input-placeholder {
                text-align: right;
            }
        @endif
    </style>
@endsection
@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>
    <script src="{{ url('modules/employee/js/general.js') }}"></script>
    <script>
        window.csrfToken = '{{ csrf_token() }}';
    </script>
@endsection
