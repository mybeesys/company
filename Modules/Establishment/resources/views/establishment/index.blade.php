@extends('establishment::layouts.master')

@section('title', __('menuItemLang.establishments'))
@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="establishment" url="establishment/create" module="establishment" :search="false" />
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="establishment" module="establishment" />
        </x-cards.card-body>
    </x-cards.card>
@endsection

@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>
    <script>
        "use strict";
        let dataTable;
        const table = $('#kt_establishment_table');
        const dataUrl = '{{ route('establishments.index') }}';
    </script>
@endsection
