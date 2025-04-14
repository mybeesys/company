@extends('layouts.app')

@section('title', __('menuItemLang.general_setting'))
@section('css')
    <style>
        .dropend .dropdown-toggle::after {
            border-left: 0;
            border-right: 0;
        }

        .fa-folder:before {
            color: #17c653 !important;

        }

        #accounts_tree_container>ul {
            text-align: justify !important;

        }

        .jstree-container-ul .jstree-children {
            text-align: justify !important;
        }

        .jstree-default .jstree-search {
            font-style: oblique !important;
            color: #1b84ff !important;
            font-weight: 700 !important;
        }

        .swal2-popup {
            width: 58em !important;
            /* max-width: 0% !important; */
        }

        .jstree-default .jstree-clicked {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .jstree-default .jstree-anchor .jstree-hovered {
            background: #beebff2e !important;
            border-radius: 8px !important;
            box-shadow: none !important;
        }

        .btn.btn-secondary.show:hover {
            background-color: transparent !important;
        }

        .select-custom {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #f3f4f6;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 10px;
            font-size: 14px;
            color: #333;
        }

        .title {
        font-size: 16px;
        font-weight: bold; /* Bold the "Lounge" text */
        color: #343a40; /* Dark gray color */
        padding-left: 10px;
      }

      .badge {
        display: inline-block;
        padding: 5px 10px; /* Adjust padding for size */
        background-color: #f8f9fa; /* Light gray background */
        border: 1px solid #ddd; /* Subtle border */
        border-radius: 5px; /* Rounded corners */
        font-size: 14px; /* Adjust font size */
        font-weight: bold; /* Bold text */
        color: #343a40; /* Text color */
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        text-align: center; /* Center the text */
      }
      .table-id {
        background-color: #e7f3ff; /* Light blue background */
        color: #004085; /* Dark blue text */
        font-weight: bold; /* Bold text */
        font-size: 14px; /* Adjust font size */
        border-radius: 5px; /* Rounded corners for the badge */
        text-align: center; /* Center align text */

      }

      .table-seats {
        font-size: 14px; /* Adjust font size */
        color: #6c757d; /* Gray color for seats info */
        font-weight: bold; /* Make text bold */
        text-align: end;
      }

    </style>


@endsection

@section('content')
    {{-- <div class="container">
        <div class="row my-6">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1> @lang('menuItemLang.general_setting')</h1>

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">
            </div>
        </div>
    </div> --}}
    {{-- <div class="container">
        <div class="row">
            @foreach ($cards as $card)
                <div class="col-3">
                    <a href="{{ route($card['route']) }}" class="link">
                        <label
                            class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 mb-5 px-4"
                            data-kt-button="true">
                            <input class="btn-check" type="radio" name="method" value="0">
                            <i class="{{ $card['icon'] }}  fs-2hx mb-2 pe-0"></i>
                            <span class="fs-7 fw-bold d-block">{{ $card['name'] }}</span>
                        </label>
                    </a>
                </div>
            @endforeach
        </div>
    </div> --}}
    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item nav-link-taxes">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#general_setting_tab">@lang('menuItemLang.general_setting')</a>
            </li>

            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#notifications_tab">@lang('general::general.notifications_templates')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#mail_settings_tab">@lang('general::general.mail_settings')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#sms_settings_tab">@lang('general::general.sms_settings')</a>
            </li>
            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#prefix_settings_tab">@lang('general::general.Prefix Settings')</a>
            </li>

            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#invoice_settings_tab">@lang('general::general.invoice_settings')</a>
            </li>

            <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#inventory_costing_tab">@lang('general::general.inventory_costing')</a>
            </li>


            {{-- <li class="nav-item">
                <a class="nav-link justify-content-center text-active-gray-800" data-bs-toggle="tab"
                    href="#establishments_tab">@lang('menuItemLang.establishments')</a>
            </li> --}}



        </ul>
        <div class="tab-content" id="myTabContent">

            <x-general::notifications.notification-settings-index :employees="$employees" :notifications_settings="$notifications_settings" />

            <x-general::mail-settings.mail-settings-index :notifications_settings_parameters="$notifications_settings_parameters" />

            <x-general::sms-settings.sms-settings-index :notifications_settings_parameters="$notifications_settings_parameters" />

            @include('general::prefix-settings.prefix-settings')
            @include('general::general-setting.invoice-tab')
            {{-- @include('general::establishments.establishments-tab') --}}
            @include('general::inventory_costing.inventory_costing')
            @include('general::invoice-setting.general-invoice-setting.invoice-tab')

        </div>

    </div>
@endsection

@section('script')
    @parent

    <script src="{{ url('js/table.js') }}"></script>
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>
    <script src="assets/plugins/custom/ckeditor/ckeditor-classic.bundle.js"></script>
    <script>
        "use strict";
        let taxesDataTable;
        const taxesTable = $('#kt_tax_table');;
        const taxesDataUrl = '{{ route('taxes') }}';

        let methodDataTable;
        const methodTable = $('#kt_payment_methods_table');;
        const methodDataUrl = '{{ route('payment-methods') }}';

        document.querySelectorAll('.link').forEach(function(link) {
            link.addEventListener('click', function(event) {
                if (event.target.tagName !== 'INPUT') {
                    event.preventDefault();
                    window.location.href = this.href;
                }
            });
        });


        $(document).ready(function() {
            $('#inventory_costing_method').select2();
            $('#currency').select2();


            @php
                $notification_names = ['new_sell', 'created_emp', 'payment_received', 'payments', 'new_booking', 'new_quotation', 'new_order', 'payment_paid', 'items_received', 'items_pending', 'purchase_order', 'low_stock_alert_notification'];
            @endphp
            @foreach ($notification_names as $notification_name)
                handleInternalNotification_{{ $notification_name }}();
                handleSMSNotification_{{ $notification_name }}();
                handleEmailNotification_{{ $notification_name }}();
                handleNotificationsSettingsForm_{{ $notification_name }}();
                initElements_{{ $notification_name }}();
            @endforeach
            mailSettingsForm();
            initMethodDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_payment_methods_table', "{{ session('locale') }}", [], [],
                'A4',
                methodTable, methodDataTable);

            handleFormFiltersDatatable();
            $('#add_payment_methods_button').on('click', function(event) {
                event.preventDefault();
                $('#kt_modal_create_add_pay_method').modal('show');
            });

            initTaxesDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_tax_table', "{{ session('locale') }}", [], [], 'A4',
                taxesTable, taxesDataTable);

            handleSearchDatatable(taxesDataTable);

            handleFormFiltersDatatable();

            $('#add_tax_button').on('click', function(event) {
                event.preventDefault();
                $('#kt_modal_create_add_tax').modal('show');
            });

            $('#kt_tax_table').on('click', '.open-tax-modal', function(event) {
                event.preventDefault();

                const row = $(this).closest('tr');
                const table = $('#kt_tax_table').DataTable();
                const data = table.row(row).data();

                let selectedTaxes = [];

                if (data && data.sub_taxes) {
                    let subTaxes = data.sub_taxes;

                    if (typeof subTaxes === 'string') {
                        try {
                            subTaxes = JSON.parse(subTaxes);
                        } catch (e) {
                            console.error('Failed to parse sub_taxes:', e);
                            subTaxes = [];
                        }
                    }

                    if (Array.isArray(subTaxes)) {
                        selectedTaxes = subTaxes.map((tax) => tax.id);
                    } else {
                        console.error('sub_taxes is not an array:', subTaxes);
                    }
                }

                console.log('Selected Taxes:', selectedTaxes);

                const taxListContainer = $('#tax-list-container-edit');
                taxListContainer.find('option').each(function() {
                    const option = $(this);
                    const taxId = parseInt(option.val());

                    if (selectedTaxes.includes(taxId)) {
                        option.prop('selected', true);
                    } else {
                        option.prop('selected', false);
                    }
                });

                if (taxListContainer.hasClass('select2-hidden-accessible')) {
                    taxListContainer.trigger('change');
                }

                const id = row.find('td:nth-child(1)').text().trim();
                const taxName = row.find('td:nth-child(2)').text().trim();
                const taxNameEn = row.find('td:nth-child(3)').text().trim();
                const taxAmount = row.find('td:nth-child(4)').text().trim();
                const isGroup = row.find('td:nth-child(5)').text().trim();
                const taxAmountContainer = $('#tax-amount-container-edit');
                const groupTaxContainer = $('#group-tax-container-edit');
                let group_tax_checkbox = 0;
                if (isGroup == 'Compound tax' || isGroup == 'ضريبة مُركبة') {
                    taxAmountContainer.hide();
                    groupTaxContainer.show();
                    group_tax_checkbox = 1;
                } else {
                    taxAmountContainer.show();
                    groupTaxContainer.hide();
                    group_tax_checkbox = 0;
                }

                $('#kt_modal_edit_tax input[name="tax_name"]').val(taxName.replace(/\([^)]*\)\s*/g, ''));
                $('#kt_modal_edit_tax input[name="tax_name_en"]').val(taxNameEn.replace(/\([^)]*\)\s*/g,
                    ''));
                $('#kt_modal_edit_tax input[name="tax_amount"]').val(taxAmount);
                $('#kt_modal_edit_tax input[name="id"]').val(id);
                $('#kt_modal_edit_tax input[name="group_tax_checkbox"]').val(group_tax_checkbox);

                $('#kt_modal_edit_tax').modal('show');
            });


            $('select[multiple]').select2({
                placeholder: "اختر",
                allowClear: false,
                closeOnSelect: false
            });

            $('#group_tax_checkbox').on('change', function() {
                const isChecked = $(this).is(':checked');
                const taxAmountContainer = $('#tax-amount-container');
                const groupTaxContainer = $('#group-tax-container');

                if (isChecked) {
                    taxAmountContainer.hide();
                    groupTaxContainer.show();
                } else {
                    taxAmountContainer.show();
                    groupTaxContainer.hide();
                }
            });


            $('#group_tax_checkbox_edit').on('change', function() {
                const isChecked = $(this).is(':checked');
                const taxAmountContainer = $('#tax-amount-container-edit');
                const groupTaxContainer = $('#group-tax-container-edit');

                if (isChecked) {
                    taxAmountContainer.hide();
                    groupTaxContainer.show();
                } else {
                    taxAmountContainer.show();
                    groupTaxContainer.hide();
                }
            });
        });


        function initMethodDatatable() {
            methodDataTable = $(methodTable).DataTable({
                processing: true,
                serverSide: true,
                ajax: methodDataUrl,

                info: false,

                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'name_ar',
                        name: 'name_ar'
                    },
                    {
                        data: 'name_en',
                        name: 'name_en'
                    },
                    {
                        data: 'description_ar',
                        name: 'description_ar'
                    },
                    {
                        data: 'description_en',
                        name: 'description_en',
                        // visible: false
                    },

                    {
                        data: 'active',
                        name: 'active',
                        // visible: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances();
                }
            });
        };

        function handleFormFiltersDatatable() {
            const filters = $('[data-kt-filter="filter"]');
            const resetButton = $('[data-kt-filter="reset"]');
            const status = $('[data-kt-filter="status"]');
            const deleted = $('[data-kt-filter="deleted_records"]');

            filters.on('click', function(e) {
                const deletedValue = deleted.val();

                dataTable.ajax.url('{{ route('payment-methods') }}?' + $.param({
                    deleted_records: deletedValue
                })).load();

                const statusValue = status.val();
                dataTable.column(6).search(statusValue).draw();
            });

            resetButton.on('click', function(e) {
                status.val(null).trigger('change');
                deleted.val(null).trigger('change');
                dataTable.search('').columns().search('').ajax.url(dataUrl)
                    .load();
            });
        };


        function initTaxesDatatable() {
            taxesDataTable = $(taxesTable).DataTable({
                processing: true,
                serverSide: true,
                ajax: taxesDataUrl,

                info: false,

                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'name_en',
                        name: 'name_en'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                    {
                        data: 'is_tax_group',
                        name: 'is_tax_group',
                        // visible: false
                    },
                    {
                        data: 'sub_taxes',
                        name: 'sub_taxes',
                        visible: false
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances();
                }
            });
        };

        $(document).on('click', '.nav-link-taxes', function() {
            taxesDataTable.ajax.reload();
        });

        $(document).on('click', '.nav-link-methods', function() {
            methodDataTable.ajax.reload();
        });



        document.querySelectorAll('[id^="terms_and_conditions_"]').forEach(element => {
            let lang = element.id.endsWith('_ar') ? 'ar' : 'en';
            let direction = lang === 'ar' ? 'rtl' : 'ltr';

            ClassicEditor
                .create(element, {
                    toolbar: ['heading', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'undo',
                        'redo'
                    ],
                    language: {
                        content: lang,
                        ui: 'en'
                    }
                })
                .then(editor => {
                    editor.editing.view.change(writer => {
                        writer.setAttribute('dir', direction, editor.editing.view.document.getRoot());
                    });
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
@endsection
