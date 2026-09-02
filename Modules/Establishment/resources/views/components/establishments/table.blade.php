@props(['columns'])
<x-cards.card>
    <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
        <x-tables.table-header model="establishment" url="establishment/create" module="establishment"
            :search="false" :addButton="dashboard_can(\Modules\Establishment\Support\EstablishmentPermissions::ESTABLISHMENT_CREATE)">
            <x-slot:filters>
                <x-tables.filters-dropdown>
                    <x-establishment::establishments.filters />
                </x-tables.filters-dropdown>
            </x-slot:filters>
        </x-tables.table-header>
    </x-cards.card-header>

    <x-cards.card-body class="table-responsive">
        <x-tables.table :columns=$columns model="establishment" module="establishment"
            :actionColumnLabel="__('establishment::general.branch_settings')" />
    </x-cards.card-body>
</x-cards.card>