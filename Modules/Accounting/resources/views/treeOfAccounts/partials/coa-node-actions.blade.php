@php
    $canAddChild = (bool) ($account->coa_can_add_child ?? true);
    $ledgerUrl = route('ledger', ['account_id' => $account->id]);
    $accountPayload = $account->only([
        'id', 'name_ar', 'name_en', 'account_type', 'account_primary_type', 'status', 'gl_code',
    ]);
@endphp

<span class="tree-actions">
    <div class="btn-group dropend">
        <button type="button"
            style="background: transparent; padding: 2px 7px 8px 13px; border-radius: 6px;"
            class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
            onclick="event.stopPropagation();">
            <i class="fas fa-cog"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end coa-action-menu"
            @if (app()->getLocale() == 'ar') dir="rtl" @endif>
            @dashboardcan([\Modules\Accounting\Support\AccountingPermissions::LEDGER_SHOW, \Modules\Accounting\Support\AccountingPermissions::ACCOUNT_STATEMENT_SHOW])
            <a class="dropdown-item ledger-link"
                href="{{ $ledgerUrl }}"
                onclick="event.stopPropagation();">
                <i class="fas fa-file-alt me-2"></i>@lang('accounting::lang.ledger')
            </a>
            @enddashboardcan
            @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_UPDATE)
                <a class="dropdown-item coa-set-account-trigger"
                    href="#"
                    data-account='@json($accountPayload)'
                    data-bs-target="#kt_modal_edit_account">
                <i class="fas fa-edit me-2"></i>@lang('messages.edit')
            </a>
            @enddashboardcan
            @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_CREATE)
            @if ($canAddChild)
                <a class="dropdown-item"
                    href="#"
                    onclick="setAccountId({{ $account->id }}, '{{ $account->account_primary_type }}')"
                    data-bs-toggle="modal"
                    data-bs-target="#kt_modal_create_account">
                    <i class="fas fa-plus me-2"></i>@lang('accounting::lang.add_account')
                </a>
            @else
                <span class="dropdown-item text-muted"
                    title="@lang('accounting::lang.cannot_add_child_account_has_movements')">
                    <i class="fas fa-lock me-2"></i>@lang('accounting::lang.add_account')
                </span>
            @endif
            @enddashboardcan
            <div class="dropdown-divider"></div>
            @if ($account->status == 'active')
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_DEACTIVATE)
                <a class="dropdown-item text-danger coa-set-account-trigger coa-status-modal-trigger"
                    href="#"
                    data-account='@json($accountPayload)'
                    data-bs-target="#kt_modal_deactive">
                    <i class="fas fa-power-off me-2"></i>@lang('messages.deactivate')
                </a>
                @enddashboardcan
            @else
                @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_ACTIVATE)
                <a class="dropdown-item text-success coa-set-account-trigger coa-status-modal-trigger"
                    href="#"
                    data-account='@json($accountPayload)'
                    data-bs-target="#kt_modal_active">
                    <i class="fas fa-power-off me-2"></i>@lang('messages.activate')
                </a>
                @enddashboardcan
            @endif
            @dashboardcan(\Modules\Accounting\Support\AccountingPermissions::TREE_DELETE)
            <form action="{{ route('delete-account') }}" method="POST" class="m-0"
                onclick="event.stopPropagation();">
                @csrf
                <input type="hidden" name="account_id" value="{{ $account->id }}">
                <button type="button" class="dropdown-item text-danger account-delete-btn"
                    onclick="event.preventDefault(); event.stopPropagation(); window.confirmDeleteAccount(this);">
                    <i class="fas fa-trash me-2"></i>@lang('messages.delete')
                </button>
            </form>
            @enddashboardcan
        </div>
    </div>
</span>
