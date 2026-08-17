@php
    $estId = $estId ?? '';
    $branchAccounts = $branchAccounts ?? [];
    $accountId = $accountId ?? ($branchAccounts[$estId] ?? $branchAccounts[(string) $estId] ?? null);
    $branchesById = $branchesById ?? collect();
    $branch = $branch ?? $branchesById->get($estId) ?? $branchesById->get((int) $estId);
    $branchName = $branchName ?? (
        $locale === 'ar'
            ? ($branch?->name ?: $branch?->name_en ?: ('#'.$estId))
            : ($branch?->name_en ?: $branch?->name ?: ('#'.$estId))
    );
    $selectName = $namePrefix.'['.$index.'][branch_accounts]['.$estId.']';
@endphp
<div class="branch-account-row" data-branch-account-row data-establishment-id="{{ $estId }}">
    <div class="branch-account-label">
        <span class="branch-account-name">{{ $branchName }}</span>
        <span class="text-muted fs-8">@lang('establishment::general.branch_gl_account')</span>
    </div>
    <div class="branch-account-select">
        <select name="{{ $selectName }}"
            class="form-select form-select-solid select-2-branch-account w-100 @error('cashier_payment_rows.'.$index.'.branch_accounts.'.$estId) is-invalid @enderror"
            data-placeholder="@lang('messages.select')"
            data-allow-clear="true">
            <option value=""></option>
            @foreach ($accounts ?? [] as $account)
                <option value="{{ $account->id }}" @selected((string) $accountId === (string) $account->id)>
                    {{ $locale === 'ar'
                        ? trim(($account->gl_code ? $account->gl_code.' — ' : '').($account->name_ar ?? ''))
                        : trim(($account->gl_code ? $account->gl_code.' — ' : '').($account->name_en ?? $account->name_ar ?? '')) }}
                </option>
            @endforeach
        </select>
        @error('cashier_payment_rows.'.$index.'.branch_accounts.'.$estId)
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</div>
