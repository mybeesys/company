<script>
(function () {
    // Sell invoice sync table
    const $syncForm = $('#zatca-sync-sell-form');
    if ($syncForm.length) {
        const $selectAll = $('#zatca-select-all');
        const $bulkBtn = $('#zatca-bulk-sync-btn');
        const $selectedCount = $('#zatca-selected-count');

        function updateSelectedCount() {
            const $enabled = $syncForm.find('.zatca-row-check:not(:disabled)');
            const count = $enabled.filter(':checked').length;
            $selectedCount.text(count);
            $bulkBtn.prop('disabled', count === 0);
            const total = $enabled.length;
            $selectAll.prop('checked', total > 0 && count === total);
            $selectAll.prop('indeterminate', count > 0 && count < total);
            $selectAll.prop('disabled', total === 0);
        }

        $selectAll.on('change', function () {
            $syncForm.find('.zatca-row-check:not(:disabled)').prop('checked', this.checked);
            updateSelectedCount();
        });

        $syncForm.on('change', '.zatca-row-check', updateSelectedCount);

        $syncForm.on('click', '.zatca-sync-one-btn', function () {
            const id = String($(this).data('transaction-id'));
            $syncForm.find('.zatca-row-check:not(:disabled)').prop('checked', false);
            $syncForm.find('.zatca-row-check[value="' + id + '"]:not(:disabled)').prop('checked', true);
            updateSelectedCount();
            $syncForm.trigger('submit');
        });

        $syncForm.on('submit', function (e) {
            if ($syncForm.find('.zatca-row-check:checked:not(:disabled)').length === 0) {
                e.preventDefault();
                return false;
            }
        });

        updateSelectedCount();
    }

    // Credit notes / sell-returns sync table
    const $returnForm = $('#zatca-sync-return-form');
    if ($returnForm.length) {
        const $returnSelectAll = $('#zatca-return-select-all');
        const $returnBulkBtn = $('#zatca-bulk-return-sync-btn');
        const $returnSelectedCount = $('#zatca-return-selected-count');

        function updateReturnSelectedCount() {
            const $enabled = $returnForm.find('.zatca-return-row-check:not(:disabled)');
            const count = $enabled.filter(':checked').length;
            $returnSelectedCount.text(count);
            $returnBulkBtn.prop('disabled', count === 0);
            const total = $enabled.length;
            $returnSelectAll.prop('checked', total > 0 && count === total);
            $returnSelectAll.prop('indeterminate', count > 0 && count < total);
            $returnSelectAll.prop('disabled', total === 0);
        }

        $returnSelectAll.on('change', function () {
            $returnForm.find('.zatca-return-row-check:not(:disabled)').prop('checked', this.checked);
            updateReturnSelectedCount();
        });

        $returnForm.on('change', '.zatca-return-row-check', updateReturnSelectedCount);

        $returnForm.on('click', '.zatca-sync-return-one-btn', function () {
            const id = String($(this).data('transaction-id'));
            $returnForm.find('.zatca-return-row-check:not(:disabled)').prop('checked', false);
            $returnForm.find('.zatca-return-row-check[value="' + id + '"]:not(:disabled)').prop('checked', true);
            updateReturnSelectedCount();
            $returnForm.trigger('submit');
        });

        $returnForm.on('submit', function (e) {
            if ($returnForm.find('.zatca-return-row-check:checked:not(:disabled)').length === 0) {
                e.preventDefault();
                return false;
            }
        });

        updateReturnSelectedCount();
    }
})();
</script>
