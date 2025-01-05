<div class="modal fade" tabindex="-1" id="group_payroll_group_modal">
    <div class="modal-dialog" style="min-width: 1500px;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Modal title</h3>
            </div>
            <div class="modal-body">
                <x-cards.card-body class="table-responsive">
                    <x-tables.table :columns=[] :actionColumn="false" :idColumn="false" model="payroll_modal" :idColumn=true
                        module="employee" />
                </x-cards.card-body>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">print</button>
            </div>
        </div>
    </div>
</div>
