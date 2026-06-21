<div class="row mt-8" dir="ltr">
    <div class="col-md-4 mb-0 col-md-offset-4">
        <div class="input-group">
            <input type="text" class="search-input form-control form-control border h-lg-35px ps-13"
                id="accounts_tree_search" placeholder="Search...">
            <span class="input-group-addon">
                <i class="ki-outline ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5"></i>
            </span>
        </div>
    </div>

    <div class="col-md-8 d-flex justify-content-end gap-2">
        <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="expand_all">@lang('accounting::lang.expand_all')</button>
        <button class="btn btn-flex btn-primary h-30px fs-7 fw-bold" id="collapse_all">@lang('accounting::lang.collapse_all')</button>
    </div>
    <div class="container d-flex">
        <div class="col-md-12" id="accounts_tree_container" style="flex: 0 0 250px;">
            <ul>
                @foreach ($account_types as $key => $value)
                    <li @if ($loop->index == 0) data-jstree='{ "opened" : true }' @endif>
                        ({{ $account_GLC[$key] }}) - {{ $value }}
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
