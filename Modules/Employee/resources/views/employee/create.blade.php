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
    @parent
    <script>
        new tempusDominus.TempusDominus($("#employmentStartDate")[0], {
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
            let typingTimer;
            let doneTypingInterval = 1000;
            let hasError = false;
            let saveButton = $('#add_employee_form_button');
            saveButton.prop('disabled', true);


            $('#isActive').change(function() {
                if ($(this).is(':checked')) {
                    $(this).val(1);
                } else {
                    showAlert("{{ __('employee::responses.change_status_warning') }}",
                        "{{ __('employee::general.diactivate') }}",
                        "{{ __('employee::general.cancel') }}", undefined,
                        true, "warning").then(function(t) {
                        if (t.isConfirmed) {
                            $(this).val(1);
                        } else {
                            $(this).val(0);
                            $('#isActive').prop('checked', true);
                        }
                    });
                }
            });
            
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
                        $('#PIN').val(response.data);
                    },
                    error: function(response) {
                        showAlert("{{ __('employee::responses.something_wrong_happened') }}",
                            "{{ __('employee::general.try_again') }}",
                            undefined, undefined,
                            false, "error");
                    }
                });
            });

            // Function to handle validation via AJAX
            function validateField(input) {
                let field = input.attr('name');
                let formData = new FormData();
                formData.append(field, input[0].files ? input[0].files[0] : input.val());
                formData.append("_token", "{{ csrf_token() }}");

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
                        checkErrors();
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
                        checkErrors();
                    }
                });
            }

            function checkErrors() {
                if ($('.is-invalid').length > 0) {
                    saveButton.prop('disabled', true);
                } else {
                    saveButton.prop('disabled', false);
                }
            }
        });
    </script>
@endsection
