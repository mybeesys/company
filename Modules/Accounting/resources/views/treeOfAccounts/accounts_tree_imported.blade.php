<div class="coa-page">
    <div class="row g-4 g-xl-5">
        <div class="col-xl-9">
            <div class="card card-flush coa-tree-card">
                <div class="card-body p-4 p-lg-5">
                    @include('accounting::treeOfAccounts.partials.coa-toolbar')

                    <div id="accounts_tree_container" class="coa-tree-container">
                        <ul>
                            @foreach ($account_types as $key => $value)
                                <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif>
                                    <span class="coa-type-heading">
                                        <span class="text-muted">({{ $account_GLC[$key] ?? '' }})</span>
                                        <span class="fw-bold">{{ $value }}</span>
                                        @php $typeSummary = $primaryTypeSummary[$key] ?? null; @endphp
                                        @if ($typeSummary)
                                            <span class="coa-balance coa-balance-aggregated ms-1" dir="ltr"
                                                title="@lang('accounting::lang.coa_primary_type_total_tooltip')">
                                                @format_currency($typeSummary['balance'])
                                            </span>
                                        @endif
                                    </span>
                                    <ul>
                                        @foreach ($imported_roots[$key] ?? [] as $rootAccount)
                                            @include('accounting::treeOfAccounts.account_tree_node', ['account' => $rootAccount])
                                        @endforeach
                                    </ul>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            @include('accounting::treeOfAccounts.partials.coa-sidebar')
        </div>
    </div>
</div>
