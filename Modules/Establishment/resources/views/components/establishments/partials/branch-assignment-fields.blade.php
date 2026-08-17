@php
    $assignedIds = array_map('intval', $row['establishment_ids'] ?? []);
    $fieldName = $namePrefix.'['.$index.'][establishment_ids][]';
    $locale = $locale ?? app()->getLocale();
@endphp
<div class="branch-assignment" data-branch-assignment>
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
</div>
