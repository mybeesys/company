@props(['role' => null])
@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees'))

@section('content')
    <form id="add_role_form" class="form d-flex flex-column flex-lg-row" method="POST" enctype="multipart/form-data"
        action="{{ route('roles.store') }}">
        @csrf
        <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
            <x-employee::form.form-card :title="__('employee::general.role_details')">
                <div class="d-flex flex-wrap">
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.name') }} ({{ __('employee::fields.required') }})"
                            value="{{ $role?->name }}" name="name" :label="__('employee::fields.name')" />
                    </x-employee::form.input-div>
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input :errors=$errors placeholder="{{ __('employee::fields.department') }}"
                            value="{{ $role?->department }}" name="department" :label="__('employee::fields.department')" />
                    </x-employee::form.input-div>
                    <x-employee::form.input-div class="mb-10 w-100 px-2">
                        <x-employee::form.input required :errors=$errors
                            placeholder="{{ __('employee::fields.rank') }} (1-999)" value="{{ $role?->rank }}" name="rank"
                            :label="__('employee::fields.rank')" />
                    </x-employee::form.input-div>
                </div>
            </x-employee::form.form-card>
            <x-employee::form.form-buttons cancelUrl="{{ url('/role') }}" />
        </div>
    </form>
@endsection

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            form('add_role_form', "{{ route('roles.create.validation') }}");
        });

        function form(id, validationUrl) {
            let saveButton = $(`#${id}_button`);
            saveButton.prop('disabled', true);

            $(`#${id} input`).on('change', function() {
                let input = $(this);
                validateField(input);
            });

            function validateField(input) {
                let field = input.attr('name');
                let formData = new FormData();
                formData.append(field, input.val());
                formData.append("_token", window.csrfToken);

                $.ajax({
                    url: validationUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function() {
                        input.siblings('.invalid-feedback ').remove();
                        input.removeClass('is-invalid');
                        checkErrors();
                    },
                    error: function(response) {
                        input.siblings('.invalid-feedback').remove();
                        input.removeClass('is-invalid');

                        let errorMsg = response.responseJSON.errors[field];
                        if (errorMsg) {
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback">' + errorMsg[0] + '</div>');
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
        }
    </script>
@endsection
