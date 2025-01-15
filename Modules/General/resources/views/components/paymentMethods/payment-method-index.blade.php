@props(['methodColumns'])
<div class="tab-pane fade" id="payemnt_methods_tab" role="tabpanel">
    <div class="card card-flush">
        <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5">
            <x-tables.table-header model="payment_methods" url="#" module="general">
                <x-slot:filters>
                </x-slot:filters>
                <x-slot:export>
                    <x-tables.export-menu id="payment_methods" />
                </x-slot:export>
            </x-tables.table-header>
        </x-cards.card-header>

        <x-cards.card-body class="table-responsive">
            <x-tables.table :columns=$methodColumns model="payment_methods" module="general" />
        </x-cards.card-body>
    </div>
</div>
