@extends('employee::layouts.master')

@section('title', __('employee::general.add_employee'))
@section('content')
    <form id="add_employee_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('employees.store') }}">
        @csrf
        <x-employee::employees.form />
    </form>
@endsection

@section('script')
    <script>
        new tempusDominus.TempusDominus($("#employment_start_date")[0], {
            localization: {
                format: "yyyy/MM/dd",
            },
            restrictions: {
                maxDate: new Date(),
            },
            display: {
                viewMode: "calendar",
                components: {
                    decades: true,
                    year: true,
                    month: true,
                    date: true,
                    hours: false,
                    minutes: false,
                    seconds: false
                }
            }
        });

        $(document).ready(function() {
            let typingTimer; // Timer identifier
            let doneTypingInterval = 1000; // Time in ms (1 second)

            // On keyup, start the countdown
            $('#add_employee_form input, #add_employee_form input[type="file"]').on('change', function() {
                let input = $(this);
                validateField(input);
            });

            $('#generate_pin').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('employees.generate.pin') }}",
                    type: 'GET',
                    success: function(response) {
                        $('#pin').val(response.data);
                    },
                    error: function(response) {
                        Swal.fire({
                            text: "{{ __('employee::responses.something_wrong_happened') }}",
                            icon: "error",
                            confirmButtonText: "{{ __('employee::general.try_again') }}",
                            customClass: {
                                confirmButton: "btn btn-danger"
                            }
                        });
                    }
                });
            });

            // Function to handle validation via AJAX
            function validateField(input) {
                let field = input.attr('name');
                let formData = new FormData();
                formData.append(field, input[0].files ? input[0].files[0] : input.val());
                formData.append("_token", "{{ csrf_token() }}");
                console.log(input.siblings);

                $.ajax({
                    url: "{{ route('employees.create.validation') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        input.siblings('.invalid-feedback ').remove();
                        input.removeClass('is-invalid');
                        $('#image_error').removeClass('d-block');
                    },
                    error: function(response) {
                        input.siblings('.invalid-feedback').remove();
                        input.removeClass('is-invalid');
                        $('#image_error').removeClass('d-block');

                        let errorMsg = response.responseJSON.errors[field];
                        if (errorMsg) {
                            input.addClass('is-invalid');
                            if (input.attr('type') === 'file') {
                                input.closest('div').after(
                                    '<div class="invalid-feedback d-block" id="image_error">' +
                                    errorMsg[0] + '</div>');
                            } else {
                                input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection
