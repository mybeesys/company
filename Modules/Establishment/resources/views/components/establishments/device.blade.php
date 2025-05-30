@props(['columns'])

<x-cards.card>
    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
        <h5>@lang('establishment::general.devices')</h5>
    </x-cards.card-header>

    <x-cards.card-body class="table-responsive">
        <x-tables.table :columns="$columns" model="device" module="device" />
    </x-cards.card-body>
</x-cards.card>