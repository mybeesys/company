<div class="container">


    <form id="company_settings_form" class="form d-flex flex-column gap-2" method="POST" enctype="multipart/form-data"
        action="{{ route('companies.update', ['id' => $company->id]) }}">
        @csrf
        <div class="d-flex flex-column flex-row-fluid gap-5">
            <div class="tab-pane fade show active" id="company_details_tab" role="tabpanel">
                <x-establishment::company.details-form :company=$company :countries=$countries :settings=$settings />

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
            return 'height: 36.05px !important; min-height: 36.05px !important;';
        });

        $('#company_settings_form').on('submit', function(e) {
            e.preventDefault();

             $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();

            let formData = new FormData(this);

            $.ajax({
                url: "{{ route('companies.update', ['id' => $company->id]) }}",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-HTTP-Method-Override': 'PATCH'
                },
                success: function(response) {
                    Swal.fire({
                        text: response.message || "{{ __('employee::responses.operation_success') }}",
                        icon: "success",
                        buttonsStyling: false,
                        confirmButtonText: "Ok",
                        customClass: { confirmButton: "btn btn-primary" }
                    });
                },
                error: function(data) {
                    if (data.status === 422 && data.responseJSON && data.responseJSON.errors) {
                        $.each(data.responseJSON.errors, function(key, value) {
                            let input = $(`[name='${key}']`);
                            if (input.length) {
                                input.addClass('is-invalid');
                                input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                            }
                        });
                    } else {
                        let errorMsg = (data.responseJSON && data.responseJSON.error)
                                        ? data.responseJSON.error
                                        : "{{ __('establishment::responses.something_wrong_happened') }}";

                        Swal.fire({
                            text: errorMsg,
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Ok",
                            customClass: { confirmButton: "btn btn-danger" }
                        });
                    }
                }
            });
        });

        $(`#company_settings_form input`).on('change', function() {
            let input = $(this);
             if (input.attr('type') !== 'file') {
                validateField(input, "{{ route('companies.update.validation') }}", $(`#company_settings_form_button`));
            }
        });
    </script>
@endsection
