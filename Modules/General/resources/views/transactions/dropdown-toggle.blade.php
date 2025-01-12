<div class="my-1 me-2">
    <button type="button" class="btn btn-success my-1 me-9"
        onclick="window.location.href='{{ url('/transaction-payment-print/' . $transaction->id) }}'">
        @lang('general.print')
    </button>
    <button type="button" class="btn btn-light-success my-1"
        onclick="window.location.href='{{ url('/transaction-export-pdf/' . $transaction->id) }}'">
        @lang('employee::general.export_as_pdf')
    </button>
</div>
