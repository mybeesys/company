@php
    $assignedCount = count(array_filter(array_map('intval', $row['establishment_ids'] ?? [])));
@endphp
<ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x catalog-item-tabs mb-5" role="tablist">
    <li class="nav-item">
        <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#{{ $detailsTabId }}" role="tab">
            @lang('establishment::general.settings_details_tab')
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-active-primary pb-4 d-inline-flex align-items-center gap-2" data-bs-toggle="tab"
            href="#{{ $assignTabId }}" role="tab">
            @lang('establishment::general.assign_to_branches_tab')
            <span class="badge badge-light-primary fw-bold rounded-pill px-3 py-2 branch-assign-tab-count">{{ $assignedCount }}</span>
        </a>
    </li>
</ul>
