@extends('establishment::layouts.master')

@section('title', __('establishment::general.company_settings'))


@section('css')
    <style>
        @media (min-width: 1300px) {
            .select2-selection.select2-selection--single {
                width: 333.24px !important;
            }
        }
    </style>
@endsection
@section('content')

    <form id="company_settings_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('companies.update', ['id' => $company->id]) }}">
        @csrf
        <div class="d-flex flex-column flex-row-fluid gap-5">
            <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
                <li class="nav-item">
                    <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                        href="#company_details_tab">@lang('establishment::general.company_details')</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                        href="#company_settings_tab">@lang('establishment::general.company_settings')</a>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="company_details_tab" role="tabpanel">
                    <x-establishment::company.details-form :company=$company :countries=$countries />
                </div>
                <div class="tab-pane fade show" id="company_settings_tab" role="tabpanel">
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
    @parent
    <script>
        $('select[name="country_id"]').select2();
        $('.select2-selection.select2-selection--single').attr('style', function(i, style) {
            return 'height: 36.05px !important;  min-height: 36.05px !important;';
        });

        $('form').on('submit', function(e) {
            e.preventDefault();

            ajaxRequest("{{ route('companies.update', ['id' => $company->id]) }}", "PATCH", $(this)
                .serializeArray()).fail(
                function(data) {
                    $.each(data.responseJSON.errors, function(key, value) {
                        $(`[name='${key}']`).addClass('is-invalid');
                        $(`[name='${key}']`).after('<div class="invalid-feedback">' + value + '</div>');
                    });
                });
        });

        $(`#company_settings_form input`).on('change', function() {
            let input = $(this);
            validateField(input, "{{ route('companies.update.validation') }}", $(`#company_settings_form_button`));
        });
    </script>
@endsection
