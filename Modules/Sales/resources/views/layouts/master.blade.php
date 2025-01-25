@extends('layouts.app')
@section('css')
    @if (session('locale') == 'ar')
        <style>
            input[type="number"]:not(.numInput) {
                text-align: right;
            }

            input[type="number"]::-webkit-input-placeholder,
            input[type="email"]::-webkit-input-placeholder {
                text-align: right;
            }
        </style>
    @endif
    <style>
        .add-new-option {
            display: flex;
            align-items: center;
            color: #2563eb;
            padding: 4px;
        }

        .add-new-option:hover {
            background-color: #f8fafc;
        }

        .select2-add-new-input {
            border: none;
            outline: none;
            width: 100%;
            padding: 2px;
        }

        html[dir="rtl"] .add-new-option i {
            margin-left: 8px;
            margin-right: 0;
        }
    </style>
    <link rel="stylesheet" href="{{ url('css/monthSelectPlugin.css') }}">
@endsection
@section('content')
    @yield('content')
@endsection

@section('script')
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>
@endsection
