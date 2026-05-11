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

        .establishment-form .select2-container {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }

        .establishment-form .select2-selection--single {
            min-height: 38px;
        }

        .establishment-form .select2-selection__rendered {
            line-height: 36px;
        }
    </style>
    <link rel="stylesheet" href="{{ url('css/monthSelectPlugin.css') }}">
@endsection
@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/establishment/js/messages.js') }}"></script>
    <script src="{{ url('js/monthSelectPlugin.js') }}"></script>
@endsection