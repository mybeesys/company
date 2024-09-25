<div class="row mt-8" dir="ltr">
    <div class="col-md-4 mb-0 col-md-offset-4">
        <div class="input-group">
            {{-- <input type="input" class="form-control" id="accounts_tree_search"> --}}
            <input type="text" class="search-input form-control form-control border h-lg-35px ps-13"
                id="accounts_tree_search" placeholder="Search...">
            <span class="input-group-addon">
                <i
                    class="ki-outline ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5"></i>
            </span>
        </div>
    </div>

    <div class="col-md-8 d-flex justify-content-end gap-2">
        <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="expand_all">@lang('accounting::lang.expand_all')</button>
        <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="collapse_all">@lang('accounting::lang.collapse_all')</button>
    </div>
    <div class="container d-flex">
        <div class="col-md-12 " id="accounts_tree_container" style="flex: 0 0 250px;">
            <ul>
                @foreach ($account_types as $key => $value)
                    <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif>
                        ({{ $account_GLC[$key] }})
                        - {{ $value }}
                        <ul>
                            @foreach ($account_sub_types->where('account_primary_type', $key)->all() as $sub_type)
                                <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif>
                                    ({{ $sub_type->gl_code }})
                                    - @if (app()->getLocale() == 'ar')
                                        {{ $sub_type->name_ar }}
                                    @else
                                        {{ $sub_type->name_en }}
                                    @endif

                                    <ul>
                                        @foreach ($accounts->where('account_sub_type_id', $sub_type->id)->all() as $account)
                                            <li
                                                @if (count($account->child_accounts) == 0) data-jstree='{ "icon" : "ki-outline ki-fasten" }' @endif>
                                                <span class="gap-1">
                                                    @if (app()->getLocale() == 'ar')
                                                        @if (!empty($account->gl_code))
                                                            ({{ $account->gl_code }})
                                                            -
                                                        @endif

                                                        {{ $account->name_ar }}
                                                    @else
                                                        @if (!empty($account->gl_code))
                                                            ({{ $account->gl_code }})
                                                        @endif
                                                        - {{ $account->name_en }}
                                                    @endif


                                                    - @format_currency($account->balance)

                                                </span>

                                                @if ($account->status == 'active')
                                                    <span><i class="fas fa-check text-success"
                                                            title="@lang('accounting::lang.active')"></i></span>
                                                @elseif($account->status == 'inactive')
                                                    <span><i class="fas fa-times text-danger" title="@lang('lang_v1.inactive')"
                                                            style="font-size: 14px;"></i></span>
                                                @endif

                                                <span class="tree-actions">
                                                    <div class="btn-group dropend">

                                                        <button type="button"
                                                            style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                                                            class="btn  dropdown-toggle" data-bs-toggle="dropdown"
                                                            aria-expanded="false">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-left" role="menu"
                                                            style="padding: 8px 15px;">
                                                            <li><a class="ledger-link"
                                                                    onclick="setAccountId({{ $account->id }})"
                                                                    href="{{ action('Modules\Accounting\Http\Controllers\TreeAccountsController@ledger', ['account_id' => $account->id]) }}"
                                                                    style="margin: 2px;">
                                                                    <i class="fas fa-file-alt"></i><span
                                                                        style="margin-left: 5px;">@lang('accounting::lang.ledger')</a>
                                                            </li>
                                                            <li>
                                                                <a class="btn-xs btn-default text-primary"
                                                                    style="margin: 2px;"
                                                                    onclick="setAccount({{ $account }}) "
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#kt_modal_edit_account">
                                                                    <i class="fas fa-edit"></i><span
                                                                        style="margin-left: 5px;">
                                                                        @lang('messages.edit')
                                                                </a>
                                                            </li>
                                                            <li><a class="btn-xs btn-default text-primary"
                                                                    style="margin: 2px;" data-bs-toggle="modal"
                                                                    onclick="setAccountId({{ $account->id }})"
                                                                    data-bs-target="#kt_modal_create_account">
                                                                    <i class="fas fa-plus"></i><span
                                                                        style="margin-left: 5px;">@lang('accounting::lang.add_account')
                                                                </a></li>

                                                            <li>
                                                                @if ($account->status == 'active')
                                                                    <a class="btn-xs btn-default text-danger"
                                                                        style="margin: 2px;" data-bs-toggle="modal"
                                                                        onclick="setAccount({{ $account }})"
                                                                        data-bs-target="#kt_modal_deactive">
                                                                        <i class="fas fa-power-off"></i><span
                                                                            style="margin-left: 5px;">
                                                                            @lang('messages.deactivate')
                                                                    </a>
                                                                @else
                                                                    <a class="btn-xs btn-default text-success"
                                                                        style="margin: 2px;" data-bs-toggle="modal"
                                                                        onclick="setAccount({{ $account }})"
                                                                        data-bs-target="#kt_modal_active">
                                                                        <i class="fas fa-power-off"></i><span
                                                                            style="margin-left: 5px;">
                                                                            @lang('messages.activate')
                                                                    </a>
                                                                @endif
                                                            </li>
                                                        </ul>
                                                    </div>



                                                </span>
                                                @if (count($account->child_accounts) > 0)
                                                    <ul>
                                                        @foreach ($account->child_accounts as $child_account)
                                                            <li
                                                                @if (count($child_account->child_accounts) == 0) data-jstree='{ "icon" : "ki-outline ki-fasten"}' @endif>

                                                                @if (app()->getLocale() == 'ar')
                                                                    @if (!empty($child_account->gl_code))
                                                                        ({{ $child_account->gl_code }})
                                                                        -
                                                                    @endif

                                                                    {{ $child_account->name_ar }}
                                                                @else
                                                                    @if (!empty($child_account->gl_code))
                                                                        ({{ $child_account->gl_code }})
                                                                    @endif
                                                                    - {{ $child_account->name_en }}
                                                                @endif
                                                                {{-- - @format_currency($child_account->balance) --}}

                                                                @if ($child_account->status == 'active')
                                                                    <span><i class="fas fa-check text-success"
                                                                            title="@lang('accounting::lang.active')"></i></span>
                                                                @elseif($child_account->status == 'inactive')
                                                                    <span><i class="fas fa-times text-danger"
                                                                            title="@lang('lang_v1.inactive')"
                                                                            style="font-size: 14px;"></i></span>
                                                                @endif
                                                                <span class="tree-actions">
                                                                    <div class="btn-group dropend">
                                                                        <button type="button"
                                                                            style="background: transparent;adding: 2px 7px 8px 13px;border-radius: 6px;"
                                                                            class="btn  dropdown-toggle"
                                                                            data-bs-toggle="dropdown"
                                                                            aria-expanded="false">
                                                                            <i class="fas fa-cog"></i>
                                                                        </button>
                                                                        <ul class="dropdown-menu"
                                                                            style="padding: 8px 15px;">
                                                                            <li><a class="btn-xs btn-default  ledger-link"
                                                                                    href="{{ action('Modules\Accounting\Http\Controllers\TreeAccountsController@ledger', $child_account->id) }}"
                                                                                    style="margin: 2px;">
                                                                                    <i class="fas fa-file-alt"></i><span
                                                                                        style="margin-left: 5px;">@lang('accounting::lang.ledger')</a>
                                                                            </li>
                                                                            <li>
                                                                                <a class=" btn-xs btn-default text-primary"
                                                                                    style="margin: 2px;"
                                                                                    onclick="setAccount({{ $child_account }}) "
                                                                                    data-bs-toggle="modal"
                                                                                    data-bs-target="#kt_modal_edit_account">
                                                                                    <i class="fas fa-edit"></i><span
                                                                                        style="margin-left: 5px;">
                                                                                        @lang('messages.edit')
                                                                                </a>
                                                                            </li>
                                                                            <li><a class="btn-xs btn-default text-primary"
                                                                                    style="margin: 2px;"
                                                                                    data-bs-toggle="modal"
                                                                                    onclick="setAccountId({{ $child_account->id }})"
                                                                                    data-bs-target="#kt_modal_create_account">
                                                                                    <i class="fas fa-plus"></i><span
                                                                                        style="margin-left: 5px;">@lang('accounting::lang.add_account')
                                                                                </a></li>

                                                                            <li>
                                                                                @if ($child_account->status == 'active')
                                                                                    <a class="btn-xs btn-default text-danger"
                                                                                        style="margin: 2px;"
                                                                                        data-bs-toggle="modal"
                                                                                        onclick="setAccount({{ $child_account }})"
                                                                                        data-bs-target="#kt_modal_deactive">
                                                                                        <i
                                                                                            class="fas fa-power-off"></i><span
                                                                                            style="margin-left: 5px;">
                                                                                            @lang('messages.deactivate')
                                                                                    </a>
                                                                                @else
                                                                                    <a class="btn-xs btn-default text-success"
                                                                                        style="margin: 2px;"
                                                                                        data-bs-toggle="modal"
                                                                                        onclick="setAccount({{ $child_account }})"
                                                                                        data-bs-target="#kt_modal_active">
                                                                                        <i
                                                                                            class="fas fa-power-off"></i><span
                                                                                            style="margin-left: 5px;">
                                                                                            @lang('messages.activate')
                                                                                    </a>
                                                                                @endif
                                                                            </li>
                                                                        </ul>
                                                                    </div>



                                                                </span>
                                                                @if (count($child_account->child_accounts) > 0)
                                                                    @include(
                                                                        'accounting::treeOfAccounts.chiled_tree',
                                                                        ['account' => $child_account]
                                                                    )
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
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
