@extends('layouts.app')

@section('title', __('franchise::lang.contracts'))

@section('content')
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2">
            <div class="d-flex align-items-center position-relative my-1">
                <span class="svg-icon svg-icon-1 position-absolute ms-4">...</span>
                <input type="text" data-kt-contract-table-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="{{ __('franchise::lang.search_contracts') }}" />
            </div>
            
            <x-tables.table-header model="franchiseContract" url="franchise/contracts/create" module="franchise">
                <x-slot:export>
                    <x-tables.export-menu id="franchiseContractTable" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="pt-0">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_contracts_table">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th>{{ __('franchise::lang.company') }}</th>
                        <th>{{ __('franchise::lang.duration') }}</th>
                        <th>{{ __('franchise::lang.start_date') }}</th>
                        <th>{{ __('franchise::lang.end_date') }}</th>
                        <th>{{ __('franchise::lang.fees') }}</th>
                        <th>{{ __('franchise::lang.status') }}</th>
                        <th class="text-end min-w-70px">{{ __('franchise::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    </tbody>
            </table>
        </x-cards.card-body>
    </div>
@stop

@section('script')
    @parent
    <script>
        $(document).ready(function() {
            const contractTable = $('#kt_contracts_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("franchise.contracts.index") }}',
                columns: [
                    { data: 'company_name', name: 'company_name' },
                    { data: 'contract_duration', name: 'contract_duration' },
                    { data: 'start_date', name: 'start_date' },
                    { data: 'end_date', name: 'end_date' },
                    { 
                        data: 'reality_fees', 
                        render: function(data) { return `<span class="badge badge-light-success fw-bold">${data} SAR</span>`; }
                    },
                    { 
                        data: 'status',
                        render: function(data) {
                            let color = data === 'active' ? 'success' : 'danger';
                            return `<div class="badge badge-light-${color}">${data}</div>`;
                        }
                    },
                    { data: 'actions', className: 'text-end', orderable: false }
                ]
            });
        });
    </script>
@endsection