<li @if (count($account->child_accounts) == 0) data-jstree='{ "icon" : "ki-outline ki-fasten" }' @endif>
    @include('accounting::treeOfAccounts.partials.coa-node-label', ['account' => $account])
    @include('accounting::treeOfAccounts.partials.coa-node-actions', ['account' => $account])

    @if (count($account->child_accounts) > 0)
        <ul>
            @foreach ($account->child_accounts as $childAccount)
                @include('accounting::treeOfAccounts.account_tree_node', ['account' => $childAccount])
            @endforeach
        </ul>
    @endif
</li>
