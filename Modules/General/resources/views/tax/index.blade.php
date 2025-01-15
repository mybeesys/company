@extends('layouts.app')

@section('title', __('menuItemLang.taxes'))
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
    </style>


@stop

@section('content')


    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="tax" url="#" module="general">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:export>
                    <x-tables.export-menu id="tax" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="tax" module="general" />
        </x-cards.card-body>
    </div>

    @include('general::tax.create')
    @include('general::tax.edit')
@stop

@section('script')
    @parent

    <script src="{{ url('js/table.js') }}"></script>
    {{-- <script type="text/javascript" src="vfs_fonts.js"></script> --}}
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_tax_table');;
        const dataUrl = '{{ route('taxes') }}';

        $(document).ready(function() {
            if (!table.length) return;
            initDatatable();
            exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_tax_table');
            handleSearchDatatable();
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




        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: dataUrl,

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

        function handleFormFiltersDatatable() {
            const filters = $('[data-kt-filter="filter"]');
            const resetButton = $('[data-kt-filter="reset"]');
            const status = $('[data-kt-filter="status"]');
            const deleted = $('[data-kt-filter="deleted_records"]');

            filters.on('click', function(e) {
                const deletedValue = deleted.val();

                dataTable.ajax.url('{{ route('taxes') }}?' + $.param({
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
    </script>

@endsection
