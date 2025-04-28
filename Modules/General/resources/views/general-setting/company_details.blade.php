<div class="container">


    <form id="company_settings_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('companies.update', ['id' => $company->id]) }}">
        @csrf
        <div class="d-flex flex-column flex-row-fluid gap-5">
            <div class="tab-pane fade show active" id="company_details_tab" role="tabpanel">
                <x-establishment::company.details-form :company=$company :countries=$countries />
            </div>
            <div class="tab-pane fade show" id="company_settings_tab" role="tabpanel">
            </div>
        </div>
    </form>



</div>




@section('script')
    @parent
    <script>
        $('select[name="country_id"]').select2();
        $('.select2-selection.select2-selection--single').attr('style', function(i, style) {
            return 'height: 36.05px !important;  min-height: 36.05px !important;';
        });

        $('#company_settings_form').on('submit', function(e) {
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
