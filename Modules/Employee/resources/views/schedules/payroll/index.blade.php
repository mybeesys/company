@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="payroll" module="employee">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:elements>
                    <x-form.input-div class="mb-md-8 min-w-200px w-100" :row=false>
                        <x-form.input class="form-control-solid" :label="__('employee::general.period')" name="periodDatePicker" />
                    </x-form.input-div>
                </x-slot:elements>
                <x-slot:export>
                    <x-tables.export-menu id="payroll" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="payroll" :actionColumn=false :idColumn=false selectColumn
                module="employee" />
        </x-cards.card-body>
    </x-cards.card>
    <x-employee::payroll.add-payroll-modal :employees=$employees :establishments=$establishments />
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>

    <script>
        let dataTable;
        const table = $('#kt_payroll_table');
        const dataUrl = '{{ route('schedules.payrolls.index') }}';

        $(document).ready(function() {
            initDatatable();
            addPayrollModal();
            $('[name="employee"]').select2({});
            $('[name="establishment"]').select2({
                minimumResultsForSearch: -1,
            });
            $('#date').flatpickr({
                plugins: [
                    monthSelectPlugin({
                        shorthand: true, // Displays the month in shorthand format (e.g., "Jan", "Feb")
                        dateFormat: "Y-m", // Format the value as "YYYY-MM"
                        altFormat: "F Y", // Displayed format, e.g., "January 2024"
                    })
                ]
            });
            $('#add_payroll_modal_form').on('submit', function(e) {
                e.preventDefault();

                let employee_ids = $('[name="employee"]').val();
                let establishment = $('[name="establishment"]').val();
                let date = $('#date').val();

                let baseUrl = `{{ route('schedules.payrolls.create') }}`;
                let queryParams =
                    `?employee_ids=${encodeURIComponent(employee_ids)}&establishment_ids=${encodeURIComponent(establishment)}&date=${encodeURIComponent(date)}`;

                window.location.href = baseUrl + queryParams;
            });

            $('#emp-select-all-btn').on('click', function() {
                $('[name="employee"]').select2('destroy');
                $('[name="employee"]').select2();
                let allValues = $('[name="employee"] option').map(function() {
                    return $(this).val();
                }).get().filter(function(value) {
                    return value !== '';
                });
                $('[name="employee"]').val(allValues).trigger('change');
            });

            $('#emp-deselect-all-btn').on('click', function() {
                $('[name="employee"]').select2('destroy');
                $('[name="employee"]').select2();
                $('[name="employee"]').val(null).trigger('change');
            });

            $('#est-select-all-btn').on('click', function() {
                $('[name="establishment"]').select2('destroy');
                $('[name="establishment"]').select2();
                let allValues = $('[name="establishment"] option').map(function() {
                    return $(this).val();
                }).get().filter(function(value) {
                    return value !== '';
                });
                $('[name="establishment"]').val(allValues).trigger('change');
            });

            $('#est-deselect-all-btn').on('click', function() {
                $('[name="establishment"]').select2('destroy');
                $('[name="establishment"]').select2();
                $('[name="establishment"]').val(null).trigger('change');
            });
        });

        function addPayrollModal() {
            $('#add_payroll_button').on('click', function(e) {
                e.preventDefault();
                $('#add_payroll_modal').modal('toggle');
            })
        }

        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: dataUrl,
                info: false,
                columns: [{
                        data: 'id',
                        name: 'id',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'employee',
                        name: 'employee',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'gross_wage',
                        name: 'gross_wage',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'net_wage',
                        name: 'net_wage',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'taxes_withheld',
                        name: 'taxes_withheld',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'tips',
                        name: 'tips',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'allowances',
                        name: 'allowances',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'deductions',
                        name: 'deductions',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        className: 'text-start px-3 py-2 border text-gray-800 fs-6',
                        orderable: false,
                        searchable: false
                    }
                ],
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances(); // Reinitialize KTMenu for the action buttons
                }
            });
        };
    </script>
@endsection
