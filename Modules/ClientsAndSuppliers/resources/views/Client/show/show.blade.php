@extends('layouts.app')

@section('title',$contact->name )
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
            background-color: #ffffff !important;
            min-height: 50px !important;
        }

        .me-3 {
            margin-right: 0 !important;
        }

        .text-active-gray-800.active {

            color: #1b84ff !important;
        }
    </style>


@stop
@section('content')

        <div class="container">
            <div class="row">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                        <h1>  @if ($contact->business_type != 'customer')
                            @lang('clientsandsuppliers::general.Supplier')

                            @else
                            @lang('clientsandsuppliers::general.client')

                            @endif {{ $contact->name }}</h1>

                    </div>
                </div>
                <div class="col-6" style="justify-content: end;display: flex;">
                   @include('clientsandsuppliers::Client.show.previous-next-clients')
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
                    @include('clientsandsuppliers::Client.show.client-information')

                    {{-- <div class="separator d-flex flex-center my-10">
                        <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
                    </div> --}}


                </div>
                <div class="col-7">

                    @include('clientsandsuppliers::Client.show.nav-tabs')

                </div>
            </div>

            <div class="separator d-flex flex-center my-10">
                <span class="text-uppercase bg-body fs-7 fw-semibold text-muted px-3"></span>
            </div>

            @include('clientsandsuppliers::Client.show.nav-tabs-main')

        </div>

@stop

@section('script')
    <script>
        $(document).ready(function() {

            $('#shipping_country').select2();
            $('#billing_country').select2();
            $('#bankInfo_country_bank').select2();
            $('#bankInfo_currency').select2();
            $('#contacts_list').select2();

            $('#contacts_list').on('change', function() {
                var selectedValue = this.value;
                var url = '{{ url('client-show') }}/' + selectedValue;
                window.location.href = url;
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
