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

        .branch-assignment .select2-container {
            width: 100% !important;
        }

        .branch-assignment .select2-container .select2-selection--multiple {
            min-height: 48px;
            padding: 6px 10px;
            border-radius: 0.475rem;
        }

        .branch-assignment .select2-container .select2-selection__choice {
            margin-top: 3px;
            margin-bottom: 3px;
            padding: 4px 8px;
            font-weight: 600;
        }

        .branch-assignment .select2-results__option {
            padding: 8px 12px;
        }

        .catalog-item-tabs {
            background: var(--bs-gray-100);
            border: 1px solid var(--bs-gray-300);
            border-radius: 0.475rem;
            padding: 0.35rem 0.85rem 0;
            gap: 0.5rem;
        }

        .catalog-item-tabs .nav-item {
            margin: 0;
        }

        .catalog-item-tabs .nav-link {
            color: var(--bs-gray-700);
            font-size: 0.95rem;
            font-weight: 700;
            border-bottom-width: 3px !important;
            padding: 0.85rem 0.75rem !important;
        }

        .catalog-item-tabs .nav-link:hover {
            color: var(--bs-primary);
        }

        .catalog-item-tabs .nav-link.active {
            color: var(--bs-primary);
            background: transparent;
        }

        .catalog-item-tabs .branch-assign-tab-count {
            min-width: 1.75rem;
            font-size: 0.8rem;
            line-height: 1;
        }

        .catalog-item-tabs .nav-link.active .branch-assign-tab-count {
            background-color: var(--bs-primary);
            color: #fff;
        }

        .branch-account-panel {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px dashed var(--bs-gray-300);
        }

        .branch-account-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .branch-account-row {
            display: grid;
            grid-template-columns: minmax(160px, 240px) minmax(0, 1fr);
            gap: 0.75rem 1.25rem;
            align-items: center;
            padding: 0.9rem 1rem;
            background: var(--bs-gray-100);
            border: 1px solid var(--bs-gray-300);
            border-radius: 0.475rem;
        }

        .branch-account-label {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
            min-width: 0;
        }

        .branch-account-name {
            font-weight: 700;
            color: var(--bs-gray-800);
            word-break: break-word;
        }

        .branch-account-empty {
            padding: 1rem 1.1rem;
            border: 1px dashed var(--bs-gray-400);
            border-radius: 0.475rem;
            color: var(--bs-gray-600);
            background: var(--bs-body-bg);
            font-size: 0.925rem;
        }

        .branch-account-select {
            min-width: 0;
        }

        .branch-account-row .select2-container {
            width: 100% !important;
        }

        @media (max-width: 767.98px) {
            .branch-account-row {
                grid-template-columns: 1fr;
            }
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