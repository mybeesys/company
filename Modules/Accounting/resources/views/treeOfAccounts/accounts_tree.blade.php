<div class="coa-page">
    <div class="row g-4 g-xl-5">
        <div class="col-xl-9">
            <div class="card card-flush coa-tree-card">
                <div class="card-body p-4 p-lg-5">
                    @include('accounting::treeOfAccounts.partials.coa-toolbar')

                    <div id="accounts_tree_container" class="coa-tree-container">
                        <ul>
                            @foreach ($account_types as $key => $value)
                                @php $typeTone = \Modules\Accounting\Support\CoaColorSystem::resolve($key, 1); @endphp
                                <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif
                                    style="--coa-accent: {{ $typeTone['accent'] }};">
                                    <span class="coa-type-heading {{ $typeTone['class'] }}"
                                        style="--coa-accent: {{ $typeTone['accent'] }};">
                                        <span class="coa-tone-code">({{ $account_GLC[$key] ?? '' }})</span>
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
                                        @foreach ($account_sub_types->where('account_primary_type', $key)->all() as $sub_type)
                                            @php
                                                $subAccounts = $accounts->where('account_sub_type_id', $sub_type->id);
                                                $subTypeBalance = (float) $subAccounts->sum(fn ($a) => (float) ($a->coa_display_balance ?? 0));
                                                $subTone = \Modules\Accounting\Support\CoaColorSystem::resolve($sub_type->account_primary_type, 2);
                                            @endphp
                                            <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif
                                                style="--coa-accent: {{ $subTone['accent'] }};">
                                                <span class="coa-subtype-heading {{ $subTone['class'] }}"
                                                    style="--coa-accent: {{ $subTone['accent'] }};">
                                                    <span class="coa-tone-code">({{ $sub_type->gl_code }})</span>
                                                    <span class="fw-semibold">
                                                        {{ app()->getLocale() == 'ar' ? $sub_type->name_ar : $sub_type->name_en }}
                                                    </span>
                                                    @if ($subAccounts->isNotEmpty())
                                                        <span class="coa-balance coa-balance-aggregated ms-1" dir="ltr"
                                                            title="@lang('accounting::lang.coa_aggregated_balance_tooltip')">
                                                            @format_currency($subTypeBalance)
                                                        </span>
                                                    @endif
                                                </span>
                                                <span class="tree-actions">
                                                    <div class="btn-group dropend">
                                                        <button type="button"
                                                            style="background: transparent; padding: 2px 7px 8px 13px; border-radius: 6px;"
                                                            class="btn dropdown-toggle" data-bs-toggle="dropdown"
                                                            onclick="event.stopPropagation();">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-end coa-action-menu"
                                                            @if (app()->getLocale() == 'ar') dir="rtl" @endif>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="setAccountId({{ $sub_type->id }}, '{{ $sub_type->account_primary_type }}')"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#kt_modal_create_sub_account">
                                                                <i class="fas fa-plus me-2"></i>@lang('accounting::lang.add_account')
                                                            </a>
                                                        </div>
                                                    </div>
                                                </span>
                                                <ul>
                                                    @foreach ($subAccounts as $account)
                                                        @include('accounting::treeOfAccounts.account_tree_node', ['account' => $account])
                                                    @endforeach
                                                </ul>
                                            </li>
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
