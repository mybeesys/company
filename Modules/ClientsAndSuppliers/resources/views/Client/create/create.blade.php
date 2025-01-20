@extends('layouts.app')

@section('title', __('clientsandsuppliers::general.add_clients'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .custom-width {
            min-width: 60%;
            width: 60%;
        }

        .custom-height {
            height: 35px;
            width: 60%;
        }

        .custom-input {
            height: 35px;
        }

        .custom-header {
            background-color: #f1f1f4 !important;
            min-height: 50px !important;
        }

        .me-3 {
            margin-right: 0 !important;
        }
    </style>


@stop
@section('content')
    <form id="client" method="POST" action="{{ route('client-save') }}">
        @csrf
        <input type="hidden" name="business_type" value="customer" />

        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1> @lang('clientsandsuppliers::general.add_clients')</h1>

                    </div>
                </div>
                <div class="col-6" style="justify-content: end;display: flex;">
                    <div class="flex-center" style="display: flex">
                        <button type="submit" data-submit ="print" class="btn btn-primary mx-2"
                            style="width: 12rem;">@lang('messages.save')</button>

                    </div>

                </div>
            </div>
        </div>


        <div class="separator d-flex flex-center my-6">
            <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
        </div>
        <div class="container">
            <div class="row">
                <div class="col-sm">

                    {{-- client information --}}
                    <div class="card" data-section="contact" style="border: 0;box-shadow: none">
                        <div class="container">
                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 required" style="width: 150px;">@lang('clientsandsuppliers::fields.client_name')
                                </label>
                                <input class="form-control form-control-solid custom-height" name="client_name" required
                                    placeholder="@lang('clientsandsuppliers::fields.client_name') / @lang('clientsandsuppliers::fields.organization_name')" id="client_name" type="text">
                            </div>
                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.mobile_number')</label>
                                <input class="form-control form-control-solid custom-height" name="mobile_number"
                                    placeholder="@lang('clientsandsuppliers::fields.mobile_number')" id="mobile_number" type="text">
                            </div>


                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.phone_number')</label>
                                <input class="form-control form-control-solid custom-height" name="phone_number"
                                    placeholder="@lang('clientsandsuppliers::fields.phone_number')" id="phone_number" type="text">
                            </div>
                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.email')</label>
                                <input class="form-control form-control-solid custom-height" name="email"
                                    placeholder="@lang('clientsandsuppliers::fields.email')" id="email" type="text">
                            </div>

                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.website')</label>
                                <input class="form-control form-control-solid custom-height" name="website"
                                    placeholder="@lang('clientsandsuppliers::fields.website')" id="website" type="text">
                            </div>
                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.tax_number')</label>
                                <input class="form-control form-control-solid custom-height" name="tax_number"
                                    placeholder="@lang('clientsandsuppliers::fields.tax_number')" id="tax_number" type="text">
                            </div>

                            <div class="d-flex align-items-center mb-5">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.commercial_register')</label>
                                <input class="form-control form-control-solid custom-height" name="commercial_register"
                                    placeholder="@lang('clientsandsuppliers::fields.commercial_register')" id="commercial_register" type="text">
                            </div>


                            <div class="d-flex  align-items-center ">
                                <label class="fs-6 fw-semibold mb-2 me-3 " style="width: 150px;">@lang('clientsandsuppliers::fields.Point of sale client')</label>
                                <div class="form-check">
                                    <input type="checkbox" style="border: 1px solid #9f9f9f;" id="point_of_sale_client"
                                        name="point_of_sale_client" class="form-check-input  my-2">
                                </div>
                            </div>
                        </div>




                        <div class="d-flex mb-5" style="align-items: center">
                            <button type="button" class="btn btn-xs btn-default text-primary add-custom-fields-btn px-1">
                                <i class="ki-outline ki-plus fs-2"></i>
                                @lang('clientsandsuppliers::fields.Add custom fields')
                            </button>

                            <span class=" mt-2" data-bs-toggle="tooltip" title="@lang('clientsandsuppliers::fields.tooltip_custom_fields')">
                                <i class="ki-outline ki-information-5 text-gray-500 fs-6"></i>
                            </span>

                        </div>

                    </div>

                    <div class="dropzone dz-clickable " style="padding: 8px 1.75rem;" id="kt_modal_upload_attachments">

                        <div class="dz-message needsclick">

                            <i class="ki-outline ki-file-up fs-2hx text-primary mx-2"></i>


                            <div class="ms-4 " style="text-align: justify">
                                <h3 class="dfs-5 fw-bold text-gray-900 mb-1 fs-6">@lang('accounting::lang.upload_attachment')</h3>
                                <span id="uploadInstructions"
                                    class="fw-semibold fs-6 text-muted">@lang('accounting::lang.upload_file')</span>
                            </div>

                        </div>
                    </div>


                    <input type="file" id="fileInput" name="attachment" style="display: none;">

                    <div class="separator d-flex flex-center my-10">
                        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                    </div>


                </div>
                <div class="col-7">

                    <div class="card-toolbar ">
                        <!--begin::Tab nav-->
                        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0 fw-bold" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a id="financial_information_tab"
                                    class="nav-link justify-content-center text-active-gray-800 active"
                                    data-bs-toggle="tab" role="tab" href="#financial_information"
                                    aria-selected="false" tabindex="-1">
                                    @lang('clientsandsuppliers::fields.financial_information')
                                </a>
                            </li>


                            <li class="nav-item" role="presentation">
                                <a id="payment_info_tab" class="nav-link justify-content-center text-active-gray-800 "
                                    data-bs-toggle="tab" role="tab" href="#payment_info" aria-selected="true">
                                    @lang('clientsandsuppliers::fields.Billing Address')
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a id="shipping_info_tab" class="nav-link justify-content-center text-active-gray-800"
                                    data-bs-toggle="tab" role="tab" href="#shipping_info" aria-selected="false"
                                    tabindex="-1">
                                    @lang('clientsandsuppliers::fields.shipping_addresses')

                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a id="attachments_tab" class="nav-link justify-content-center text-active-gray-800"
                                    data-bs-toggle="tab" role="tab" href="#attachments" aria-selected="false"
                                    tabindex="-1">
                                    @lang('clientsandsuppliers::fields.bank_account_information')
                                </a>
                            </li>

                            <li class="nav-item" role="presentation">
                                <a id="clientContactsCard_tab"
                                    class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                                    role="tab" href="#clientContactsCard" aria-selected="false" tabindex="-1">
                                    @lang('clientsandsuppliers::fields.client_contacts')
                                </a>
                            </li>


                        </ul>
                        <!--end::Tab nav-->
                    </div>


                    <div class="tab-content">
                        <div id="financial_information" class="card-body p-0 tab-pane fade show active" role="tabpanel"
                            aria-labelledby="financial_information_tab">
                            @include('clientsandsuppliers::Client.create.financial_information')

                        </div>
                    </div>


                    <div class="tab-content">
                        <div id="payment_info" class="card-body p-0 tab-pane fade show" role="tabpanel"
                            aria-labelledby="payment_info_tab">

                            @include('clientsandsuppliers::Client.create.billingCard')


                        </div>
                    </div>

                    <div class="tab-content ">
                        <div id="shipping_info" class="card-body p-0 tab-pane fade show" role="tabpanel"
                            aria-labelledby="shipping_info_tab">

                            @include('clientsandsuppliers::Client.create.shippingCard')

                        </div>
                    </div>

                    <div class="tab-content">
                        <div id="attachments" class="card-body p-0 tab-pane fade show" role="tabpanel"
                            aria-labelledby="attachments_tab">
                            @include('clientsandsuppliers::Client.create.bankAccountCard')

                        </div>
                    </div>
                    <div class="tab-content">
                        <div id="clientContactsCard" class="card-body p-0 tab-pane fade show" role="tabpanel"
                            aria-labelledby="clientContactsCard_tab">
                            @include('clientsandsuppliers::Client.create.clientContactsCard')

                        </div>
                    </div>






                </div>
            </div>

        </div>






    </form>

    @include('accounting::journalEntry.create-account')

@stop

@section('script')
    <script>
        $(document).ready(function() {

            $('#shipping_country').select2();
            $('#billing_country').select2();
            $('#bankInfo_country_bank').select2();
            $('#bankInfo_currency').select2();
            $('#payment_terms').select2();
            $('.kt_ecommerce_select2_account').select2({
                ajax: {
                    url: '{{ route('accounts-dropdown') }}',
                    dataType: 'json',
                    processResults: function(data) {
                        return {
                            results: data
                        }
                    },
                },

                language: {
                    noResults: function() {
                        var newAccountText = "@lang('accounting::lang.add_account')";
                        var $newAccountButton = $(
                            '<a class="link-underline" data-bs-toggle="modal" data-bs-target="#kt_modal_create_account" id="addNewAccountBtn">' +
                            newAccountText + '</a>'
                        );
                        $newAccountButton.on('click', function() {
                            $('.kt_ecommerce_select2_account').select2('close');
                        });
                        return $newAccountButton;
                    }
                },

                escapeMarkup: function(markup) {
                    return markup;
                },

                templateResult: function(data) {
                    return data.html || data.text;
                },
                templateSelection: function(data) {
                    return data.text;
                }
            });

        });

        $(document).on('shown.bs.modal', '#kt_modal_create_account', function() {
            $(this).find('#kt_ecommerce_select2_account_type').select2({
                dropdownParent: $('#kt_modal_create_account')
            });

        });


        $('#addAccountForm').on('submit', function(e) {
            e.preventDefault();

            $('#submitBtn .indicator-label').hide();
            $('#submitBtn .indicator-progress').show();

            $.ajax({
                url: "{{ route('store-account') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    $('#kt_modal_create_account').modal('hide');
                    $('#addAccountForm')[0].reset();
                },
                error: function(xhr) {},
                complete: function() {
                    $('#submitBtn .indicator-label').show();
                    $('#submitBtn .indicator-progress').hide();
                }
            });
        });


        document.getElementById('addContactCard').addEventListener('click', function() {
            const container = document.getElementById('contactCardContainer');
            const firstCard = container.querySelector('.contact-card');
            const newCard = firstCard.cloneNode(true);

            const inputs = newCard.querySelectorAll('input');
            inputs.forEach(input => input.value = '');

            const cardIndex = container.querySelectorAll('.contact-card').length + 1;
            newCard.querySelector('h4').textContent = `@lang('clientsandsuppliers::fields.contact') (${cardIndex})`;

            const deleteButton = newCard.querySelector('.remove-contact-card');
            deleteButton.style.display = 'block';
            deleteButton.addEventListener('click', function() {
                newCard.remove();
                updateCardIndexes();
            });

            container.appendChild(newCard);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const firstDeleteButton = document.querySelector(
                '#contactCardContainer .contact-card .remove-contact-card');
            if (firstDeleteButton) {
                firstDeleteButton.style.display = 'none';
            }
        });

        function updateCardIndexes() {
            const cards = document.querySelectorAll('#contactCardContainer .contact-card');
            cards.forEach((card, index) => {
                card.querySelector('h4').textContent = `@lang('clientsandsuppliers::fields.contact') (${index + 1})`;
            });
        }
        ////////////////////////////////////////////////////////////////
        document.querySelectorAll('.add-custom-fields-btn').forEach(button => {
            button.addEventListener('click', function() {

                const card = this.closest('.card');
                const container = card.querySelector('.container');

                const section = card.dataset.section;

                const row = document.createElement('div');
                row.className = 'row';

                const col1 = document.createElement('div');
                col1.className = 'col-sm';

                const field1 = document.createElement('div');
                field1.className = 'fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid';

                const input1 = document.createElement('input');
                input1.className = 'form-control form-control-solid custom-input';
                input1.name = `${section}_customLable[]`;
                input1.placeholder = "@lang('clientsandsuppliers::fields.customLable')";
                input1.type = 'text';

                field1.appendChild(input1);
                col1.appendChild(field1);

                const col2 = document.createElement('div');
                col2.className = 'col-sm';

                const field2 = document.createElement('div');
                field2.className = 'fv-row mb-5 fv-plugins-icon-container fv-plugins-bootstrap5-row-valid';

                const input2 = document.createElement('input');
                input2.className = 'form-control form-control-solid custom-input';
                input2.name = `${section}_customValue[]`;
                input2.placeholder = "@lang('clientsandsuppliers::fields.customValue')";
                input2.type = 'text';

                field2.appendChild(input2);
                col2.appendChild(field2);

                row.appendChild(col1);
                row.appendChild(col2);

                container.appendChild(row);
            });
        });


        //////////////////////////////////
        document.getElementById('kt_modal_upload_attachments').addEventListener('click', function() {
            document.getElementById('fileInput').click();
        });


        document.getElementById('fileInput').addEventListener('change', function(event) {
            const files = event.target.files;
            const uploadInstructions = document.getElementById('uploadInstructions');


            if (files.length > 0) {
                let fileNames = [];
                for (let i = 0; i < files.length; i++) {
                    fileNames.push(files[i].name);
                }

                uploadInstructions.textContent = fileNames.join(', ');
            } else {

                uploadInstructions.textContent = 'Upload file';
            }
        });
        //////////////////////
        document.getElementById('copyBillingAddress').addEventListener('change', function() {
            if (this.checked) {
                const shippingCountry = document.getElementById('shipping_country');
                const billingCountry = document.getElementById('billing_country');

                shippingCountry.value = billingCountry.value;

                shippingCountry.dispatchEvent(new Event('change'));
                document.getElementById('shipping_street_name').value = document.getElementById(
                    'billing_street_name').value;
                document.getElementById('shipping_city').value = document.getElementById('billing_city').value;
                document.getElementById('shipping_state').value = document.getElementById('billing_state').value;
                document.getElementById('shipping_postal_code').value = document.getElementById(
                    'billing_postal_code').value;
            } else {
                const shippingCountry = document.getElementById('shipping_country');
                shippingCountry.value = "";
                shippingCountry.dispatchEvent(new Event('change'));

                document.getElementById('shipping_street_name').value = '';
                document.getElementById('shipping_city').value = '';
                document.getElementById('shipping_state').value = '';
                document.getElementById('shipping_postal_code').value = '';
            }
        });
    </script>

@stop
