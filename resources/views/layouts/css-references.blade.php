<link rel="canonical" href="http://preview.keenthemes.comindex.html" />
<link rel="shortcut icon" href="/assets/media/logos/1-14.png" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
<link href="/assets/plugins/custom/fullcalendar/fullcalendar.bundle{{ $rtl_files }}.css" rel="stylesheet"
    type="text/css" />
<link href="/assets/plugins/custom/datatables/datatables.bundle{{ $rtl_files }}.css" rel="stylesheet"
    type="text/css" />
<link href="/assets/plugins/global/plugins.bundle{{ $rtl_files }}.css" rel="stylesheet" type="text/css" />
<link href="/assets/css/style.bundle{{ $rtl_files }}.css" rel="stylesheet" type="text/css" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200..1000&family=League+Gothic&display=swap"
    rel="stylesheet">

<style>
    .page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        background: rgba(255, 255, 255, 0.75);
        /* Semi-transparent white background */
        z-index: 9999;
    }

    .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    div {
        scrollbar-width: auto;
    }

    body {
        font-family: 'Cairo', sans-serif;
        font-optical-sizing: 'auto';
        font-style: normal;
    }

    .dropend .dropdown-toggle::after {
        border-left: 0;
        border-right: 0;
    }

    tr:hover:not(.not-hover) {
        background: #cae0fa !important;
        /* font-style: italic; */

        /* font-weight: bold !important; */
    }

    input.no-spin::-webkit-inner-spin-button,
    input.no-spin::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input.no-spin {
        -moz-appearance: textfield;
    }

    .form-check:not(.form-switch) .form-check-input[type=checkbox] {

        border: 1px solid #9b94949e !important;
    }

    .form-control.form-control-solid {
        border-color: #9b94949e !important;
    }

    .select2-container .select2-selection--single {
        height: 43.2px !important;
    }

    .link-underline {
        text-decoration: underline !important;
        cursor: pointer !important;
        color: #007bff !important;
    }

    .link-underline:hover {
        color: #0056b3 !important;
        text-decoration: underline !important;
    }

    .menu-item-custom:hover {
        transition: color 0.2s ease;
        background-color: var(--bs-primary-light);
        color: var(--bs-primary);
    }

    .menu-item-custom {
        display: block;
        border-radius: 11px;
    }

    .menu-item-custom a {
        padding: 0.65rem 1rem;
        transition: none;
        outline: none !important;
    }

    .select2-container .select2-selection--single .select2-selection__clear {
        position: absolute !important;
    }
</style>

@if ($local == 'ar')
    <style>
        .select2-container--bootstrap5 .select2-dropdown .select2-results__option.select2-results__option--selected:after {
            left: 1.25rem;
            right: auto;
        }

        .select2-selection__rendered {
            padding-left: 0px !important;
        }

        .select2-container .select2-selection--single .select2-selection__clear {
            right: auto;
            left: 3.5rem;
        }

        .select2-selection__choice__display {
            margin-right: 20px;
        }
    </style>
@else
    <style>
        .select2-selection__rendered {
            padding-right: 0px !important;
        }
    </style>
@endif
<style>
    .select2-container .select2-selection--single {
        height: auto;
    }
</style>
