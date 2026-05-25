<div class="modal fade" id="convertToInvoiceModal" tabindex="-1" aria-labelledby="convertToInvoiceModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertToInvoiceModalLabel">@lang('sales::general.convert-to-invoice')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="@lang('sales::general.close')"></button>
            </div>
            <form id="convert-to-invoice" method="get" action="{{ route('convert-to-invoice') }}">
                <div class="modal-body">

                    <label for="quotation-items" class="form-label">@lang('sales::lang.quotation')</label>
                    @if ($quotations->isEmpty())
                        <div class="alert alert-warning mb-0">
                            @lang('sales::lang.quotation_expired_no_convertible')
                        </div>
                    @else
                        <select id="quotation-items" name="quotation_id" required
                            class="form-select select-2 form-select-solid">
                            @foreach ($quotations as $quotation)
                                <option value="{{ $quotation->id }}">{{ $quotation->ref_no }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    <button type="submit" class="btn btn-primary" @if ($quotations->isEmpty()) disabled @endif>
                        @lang('sales::lang.Create a sales invoice')
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
