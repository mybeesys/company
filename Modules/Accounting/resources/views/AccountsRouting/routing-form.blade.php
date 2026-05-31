<form id="accounts-routing" method="POST" action="{{ route('accounts-routing-store') }}">
    @csrf
    <div class="alert alert-light-warning border border-warning border-dashed mb-5">
        <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
        @if (app()->getLocale() === 'ar')
            عند عدم توفر حساب في تبويب «جرد دوري» أو حسابات بديلة في الشجرة، قد يُستخدم حساب المشتريات/تكلفة المبيعات كملاذ أخير حتى لا تتوقف القيود.
        @else
            If no account is set under the «Periodic inventory» tab (and no fallback exists in the chart), the system may still use Purchases/COGS as a last resort so posting does not fail.
        @endif
    </div>
    <div class="d-flex flex-row-fluid gap-5">
        <ul
            class="nav nav-tabs nav-pills rounded shadow-sm p-5 flex-row flex-md-column me-5 mb-3 mb-md-0 fs-6 min-h-450px">
            <li class="nav-item w-md-200px me-0 py-1">
                <a class="nav-link py-3 active" data-bs-toggle="tab" href="#accounts-routing-sales-tab">
                    @lang('menuItemLang.sales')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0 py-1">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#accounts-routing-purchases-tab">
                    @lang('menuItemLang.purchases')
                </a>
            </li>
            <li class="nav-item w-md-200px me-0 py-1">
                <a class="nav-link py-3" data-bs-toggle="tab" href="#accounts-routing-periodic-inventory-tab">
                    @lang('accounting::lang.periodic_inventory_routing_tab')
                </a>
            </li>
        </ul>

        <div class="tab-content w-100" id="accountsRoutingSubTabContent">
            <div class="tab-pane fade show active" id="accounts-routing-sales-tab" role="tabpanel">
                @include('accounting::AccountsRouting.sales.sales-tab')
            </div>
            <div class="tab-pane fade" id="accounts-routing-purchases-tab" role="tabpanel">
                @include('accounting::AccountsRouting.purchases.purchases-tab')
            </div>
            <div class="tab-pane fade" id="accounts-routing-periodic-inventory-tab" role="tabpanel">
                @include('accounting::AccountsRouting.periodic-inventory.periodic-inventory-tab')
            </div>
        </div>
    </div>

    <button type="submit" style="border-radius: 6px;" class="btn btn-primary w-200px mt-5">
        @lang('messages.save')
    </button>
</form>
