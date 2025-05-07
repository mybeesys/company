@extends('report::layouts.master')

@section('title', __('menuItemLang.sales_report'))
@section('content')
@if (count($typesOfService) == 0)
<div class="card1 h-md-100 my-5" dir="ltr">
    <div class="card-body d-flex flex-column flex-center">
        <div class="mb-2 px-20" style="place-items: center;">


            <div class="py-10 text-center">
                <img src="/assets/media/illustrations/empty-content.svg" class="theme-light-show w-200px"
                    alt="">
                <img src="/assets/media/illustrations/empty-content.svg" class="theme-dark-show w-200px"
                    alt="">
            </div>
            <h4 class="fw-semibold text-gray-800 text-center  lh-lg">
                <span class="fw-bolder"> @lang('product::lang.no_typeOfServices')</span> <br>
            </h4>
            <a href="{{ route('typeService.create') }}"
                class="btn btn-primary fv-row flex-md-root my-3 min-w-150px mw-250px">@lang('product::lang.add_type_of_services')</a>
        </div>

    </div>
</div>
@else
<div class="card card-flush">
    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
        <x-tables.table-header model="typeService" url="type-service-create" module="product">
            <x-slot:filters>
            </x-slot:filters>
            <x-slot:export>

                <x-tables.export-menu id="product" />
            </x-slot:export>
        </x-tables.table-header>
    </x-cards.card-header>

    <x-cards.card-body class="table-responsive">
        <x-tables.table :columns=$columns model="typeService" module="product" />
    </x-cards.card-body>
</div>
@endif


@endsection

@section('script')
@parent
<script src="{{ url('/js/table.js') }}"></script>
<script>
    const table = $('#kt_typeService_table');;
    const dataUrl = "{{ route('type-service') }}";
    let currentLang = "{{ app()->getLocale() }}";
    $(document).ready(function() {
        if (!table.length) return;
        initDatatable();
        exportButtons([0, 1, 2, 3, 4, 5, 6], '#kt_typeService_table');
        handleSearchDatatable();
        handleFormFiltersDatatable();

        function initDatatable() {
            dataTable = $(table).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: dataUrl,

                },
                info: false,

                columns: [{
                        data: 'id',
                        name: 'id',
                    },
                    {
                        data: 'name',
                        name: 'nmae'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'packing_charge',
                        name: 'packing_charge'
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


    });
</script>
@endsection