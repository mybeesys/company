@props(['taxesColumns'])
<div class="tab-pane fade show active" id="taxes_tab" role="tabpanel">
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
            <x-tables.table :columns="$taxesColumns" model="tax" module="general" />
        </x-cards.card-body>
    </div>
</div>