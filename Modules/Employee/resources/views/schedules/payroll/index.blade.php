@extends('employee::layouts.master')

@section('title', __('menuItemLang.shift_schedule'))

@section('content')
    <x-cards.card>
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="shift" :addButton=false :idColumn=false module="employee">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:elements>
                    <x-form.input-div class="mb-md-8 min-w-200px w-100" :row=false>
                        <x-form.input class="form-control-solid" :label="__('employee::general.period')" name="periodDatePicker" />
                    </x-form.input-div>
                </x-slot:elements>
                <x-slot:export>
                    <x-tables.export-menu id="shift" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>
        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$columns model="shift" :actionColumn=false :idColumn=false selectColumn module="employee" />
        </x-cards.card-body>
    </x-cards.card>
@endsection


@section('script')
    @parent
    <script src="{{ url('/js/table.js') }}"></script>
    <script type="text/javascript" src="/vfs_fonts.js"></script>

    <script>
       
    </script>
@endsection
