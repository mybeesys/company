@php
    $assignedIds = array_values(array_unique(array_filter(array_map('intval', $row['establishment_ids'] ?? []))));
    $branchAccounts = is_array($row['branch_accounts'] ?? null) ? $row['branch_accounts'] : [];
    $fieldName = $namePrefix.'['.$index.'][establishment_ids][]';
    $locale = $locale ?? app()->getLocale();
    $branchesById = collect($branchOptions ?? [])->keyBy('id');
@endphp
<div class="branch-assignment" data-branch-assignment data-with-accounts>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <label class="form-label fw-semibold mb-0">@lang('establishment::general.assign_to_branches')</label>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-light-primary branch-assign-all">
                @lang('establishment::general.select_all_branches')
            </button>
            <button type="button" class="btn btn-sm btn-light branch-assign-none">
                @lang('establishment::general.clear_branches')
            </button>
        </div>
    </div>
    <p class="text-muted fs-7 mb-3">@lang('establishment::general.assign_payment_method_branches_hint')</p>
    <select name="{{ $fieldName }}"
        class="form-select form-select-solid select-2-branch-assign w-100"
        multiple
        data-placeholder="@lang('establishment::general.select_branches')"
        data-allow-clear="true">
        @foreach ($branchOptions ?? [] as $branch)
            <option value="{{ $branch->id }}" @selected(in_array((int) $branch->id, $assignedIds, true))>
                {{ $locale === 'ar' ? ($branch->name ?: $branch->name_en) : ($branch->name_en ?: $branch->name) }}
            </option>
        @endforeach
    </select>

    <div class="branch-account-panel" data-branch-account-panel>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <label class="form-label fw-semibold mb-0">@lang('establishment::general.branch_accounts_heading')</label>
        </div>
        <p class="text-muted fs-7 mb-3">@lang('establishment::general.branch_accounts_hint')</p>
        <div class="branch-account-list" data-branch-account-list>
            @foreach ($assignedIds as $estId)
                @include('establishment::components.establishments.partials.branch-account-row', [
                    'index' => $index,
                    'estId' => $estId,
                    'namePrefix' => $namePrefix,
                    'accounts' => $accounts ?? [],
                    'accountId' => $branchAccounts[$estId] ?? $branchAccounts[(string) $estId] ?? null,
                    'branchesById' => $branchesById,
                    'locale' => $locale,
                ])
            @endforeach
        </div>
        <div class="branch-account-empty {{ $assignedIds !== [] ? 'd-none' : '' }}" data-branch-account-empty>
            @lang('establishment::general.branch_accounts_empty')
        </div>
    </div>
</div>
