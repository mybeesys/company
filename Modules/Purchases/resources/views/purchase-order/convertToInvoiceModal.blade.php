<div class="modal fade" id="convertToInvoiceModal" tabindex="-1" aria-labelledby="convertToInvoiceModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="convertToInvoiceModalLabel">@lang('purchases::general.convert-to-invoice')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    aria-label="@lang('sales::general.close')"></button>
            </div>
            <form id="convert-to-invoice" method="get" action="{{ route('convert-po-to-invoice') }}">
                <div class="modal-body">
                    <label for="po-items" class="form-label">@lang('menuItemLang.purchase-order')</label>
                    @if ($poes->isEmpty())
                        <div class="alert alert-warning mb-0">
                            @lang('purchases::lang.no_purchase_order_for_convert')
                        </div>
                    @else
                        <select id="po-items" name="po_id" required
                            class="form-select select-2 form-select-solid">
                            @foreach ($poes as $po)
                                <option value="{{ $po->id }}">{{ $po->transaction_date }} - {{ $po->ref_no }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    @if ($poes->isEmpty())
                        @dashboardcan(\Modules\Purchases\Support\PurchasesPermissions::ORDERS_CREATE)
                        <a href="{{ route('create-purchase-order') }}" class="btn btn-primary">
                            @lang('purchases::general.add_purchase_order')
                        </a>
                        @enddashboardcan
                    @else
                        <button type="submit" class="btn btn-primary">@lang('purchases::lang.Create a sales invoice')</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
