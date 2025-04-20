<div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="filterModalLabel"><i class="bi bi-funnel fs-2 mx-1"></i>
                    @lang('sales::lang.Sales filtering')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            @if (isset($page) && $page == 'quotations')
                                <label for="sale_date_range" class="form-label">@lang('sales::fields.issue_date')</label>
                            @elseif (isset($page) && $page == 'purchases')
                            <label for="sale_date_range" class="form-label">@lang('purchases::fields.transaction_date')</label>

                            @else
                                <label for="sale_date_range" class="form-label">@lang('sales::fields.transaction_date')</label>
                            @endif

                            <input type="text" class="form-control" id="sale_date_range" name="sale_date_range">
                        </div>
                        <div class="col-md-6 mb-3">
                            @if (isset($page) && $page == 'quotations')
                                <label for="due_date_range" class="form-label">@lang('sales::fields.Expiry Date')</label>
                            @else
                                <label for="due_date_range" class="form-label">@lang('sales::fields.due_date')</label>
                            @endif
                            <input type="text" class="form-control" id="due_date_range" name="due_date_range">
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            @if (isset($page) && $page == 'purchases')
                                <label for="customer" class="form-label">@lang('menuItemLang.suppliers')</label>
                            @else
                                <label for="customer" class="form-label">@lang('sales::lang.clients')</label>
                            @endif
                            <select class="form-select" id="customer" name="customer">
                                <option value="">@lang('messages.view_all')</option>
                                @foreach ($clients as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="favorite-filter" class="form-label">@lang('sales::lang.Favorite invoices')</label>
                            <select id="favorite-filter" class="form-select">
                                <option value="">@lang('messages.view_all')</option>
                                <option value="1">@lang('messages.view_favorite')</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        @if (!isset($page) || (isset($page) && $page == 'purchases'))
                            <div class="col-md-6 mb-3">
                                <label for="payment_status" class="form-label">@lang('sales::fields.payment_status')</label>
                                <select id="payment_status" class="form-select">
                                    <option value="">@lang('messages.view_all')</option>
                                    <option value="paid">@lang('general::lang.paid')</option>
                                    <option value="due">@lang('general::lang.due')</option>
                                    <option value="partial">@lang('general::lang.partial')</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        {{-- <button type="button" class="btn btn-warning" id="clearFilter">@lang('sales::lang.Remove filter')</button> --}}
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">@lang('messages.cancel')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
