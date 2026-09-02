@props(['modules', 'disabled' => false, 'rolePermissions' => null, 'embedded' => false])
@php
    $hint = \Modules\Employee\Support\DashboardPermissionHint::class;
    $actions = ['show', 'print', 'create', 'update', 'delete'];
    $actionTitles = [
        'show' => __('employee::general.show'),
        'print' => __('employee::general.print'),
        'create' => __('employee::general.create'),
        'update' => __('employee::general.edit'),
        'delete' => __('employee::general.deletion'),
    ];
    $uid = 'ems'.substr(md5(spl_object_hash($modules).($disabled ? 'd' : 'e')), 0, 8);
    $moduleIndex = 0;
@endphp
@once
<style>
    .ems-perm-shell {
        --ems-border: #eef1f5;
        --ems-muted: #7e8299;
        --ems-text: #3f4254;
        --ems-radius: 14px;
        border: 1px solid var(--ems-border);
        border-radius: var(--ems-radius);
        box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        background: #fff;
        overflow: hidden;
    }
    .ems-perm-shell__head {
        padding: 1.15rem 1.25rem 1rem;
        border-bottom: 1px solid var(--ems-border);
        background: linear-gradient(180deg, #fff 0%, #fbfcfe 100%);
    }
    .ems-perm-shell__title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--ems-text);
        margin: 0;
    }
    .ems-perm-shell__lead {
        color: var(--ems-muted);
        font-size: .8rem;
        line-height: 1.55;
        margin: .35rem 0 0;
        max-width: 46rem;
    }
    .ems-perm-legend {
        display: none;
        align-items: flex-start;
        gap: .55rem;
        margin-top: .85rem;
        padding: .7rem .85rem;
        border-radius: .75rem;
        background: linear-gradient(135deg, var(--bs-primary-light, #f8efcf) 0%, #fff 72%);
        border: 1px solid var(--bs-primary-border-subtle, #eed592);
        color: var(--bs-gray-700);
        font-size: .78rem;
        line-height: 1.55;
    }
    .ems-perm-legend.is-visible {
        display: flex;
    }
    .ems-perm-legend__icon {
        color: var(--bs-primary);
        font-size: 1.05rem;
        margin-top: .08rem;
        flex-shrink: 0;
    }
    .ems-perm-legend__text { flex: 1; min-width: 0; }
    .ems-perm-legend__close {
        flex-shrink: 0;
        width: 1.5rem;
        height: 1.5rem;
        border: 0;
        border-radius: .4rem;
        background: transparent;
        color: #a1a5b7;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        margin-top: -.1rem;
        margin-inline-start: .15rem;
    }
    .ems-perm-legend__close:hover {
        background: rgba(15, 23, 42, .06);
        color: #3f4254;
    }
    .ems-perm-legend__close i { font-size: .95rem; }
    .ems-perm-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .65rem;
        padding: .85rem 1.25rem;
        border-bottom: 1px solid var(--ems-border);
        background: #fff;
    }
    .ems-perm-search {
        position: relative;
        flex: 1 1 16rem;
        min-width: 12rem;
    }
    .ems-perm-search i {
        position: absolute;
        inset-inline-start: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ems-muted);
        pointer-events: none;
    }
    .ems-perm-search .form-control {
        padding-inline-start: 2.4rem;
        background: #f9fafb;
        border-color: var(--ems-border);
        min-height: 42px;
    }
    .ems-perm-toolbar__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .45rem;
    }
    .ems-perm-total {
        font-size: .78rem;
        font-weight: 600;
        color: var(--bs-gray-700);
        background: var(--bs-primary-light, #f8efcf);
        border-radius: 999px;
        padding: .35rem .7rem;
        white-space: nowrap;
    }
    .ems-perm-chips {
        display: flex;
        flex-wrap: nowrap;
        gap: .45rem;
        padding: .75rem 1.25rem;
        overflow-x: auto;
        border-bottom: 1px solid var(--ems-border);
        scrollbar-width: thin;
        -webkit-overflow-scrolling: touch;
    }
    .ems-perm-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border: 1px solid var(--ems-border);
        background: #fff;
        color: var(--ems-text);
        border-radius: 999px;
        padding: .35rem .7rem;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
        transition: border-color .15s ease, background .15s ease, color .15s ease;
    }
    .ems-perm-chip:focus-visible,
    .ems-perm-module__head:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }
    .ems-perm-chip:hover,
    .ems-perm-chip.is-active {
        border-color: var(--bs-primary);
        background: var(--bs-primary-light, #f8efcf);
        color: var(--bs-text-primary, #946f11);
    }
    .ems-perm-chip__count {
        min-width: 1.15rem;
        height: 1.15rem;
        border-radius: 999px;
        background: #f1f3f7;
        color: var(--ems-muted);
        font-size: .65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 .28rem;
    }
    .ems-perm-chip.is-active .ems-perm-chip__count,
    .ems-perm-chip.has-selected .ems-perm-chip__count {
        background: var(--bs-primary);
        color: #fff;
    }
    .ems-perm-list {
        padding: .85rem;
        display: flex;
        flex-direction: column;
        gap: .65rem;
        background: #f7f8fa;
        min-height: 0;
    }
    .ems-perm-empty {
        display: none;
        text-align: center;
        color: var(--ems-muted);
        font-size: .85rem;
        padding: 2rem 1rem;
        flex-shrink: 0;
    }
    .ems-perm-empty.is-visible { display: block; }
    .ems-perm-module {
        background: #fff;
        border: 1px solid var(--ems-border);
        border-radius: 12px;
        overflow: hidden;
        scroll-margin-top: 1rem;
        flex: 0 0 auto;
    }
    .ems-perm-module.is-hidden { display: none; }
    .ems-perm-module__head {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .65rem;
        width: 100%;
        min-height: 3.5rem;
        padding: .85rem 1rem;
        background: #fff;
        border: 0;
        text-align: start;
        cursor: pointer;
    }
    .ems-perm-module__head:hover { background: #fcfcfd; }
    .ems-perm-module__icon {
        width: 2rem;
        height: 2rem;
        border-radius: .6rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--bs-primary-light, #f8efcf);
        color: var(--bs-primary);
        flex-shrink: 0;
    }
    .ems-perm-module__meta { min-width: 0; flex: 1; }
    .ems-perm-module__name {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: var(--ems-text);
        display: flex;
        align-items: center;
        gap: .4rem;
        min-width: 0;
    }
    .ems-perm-module__name span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ems-perm-module__sub {
        font-size: .72rem;
        color: var(--ems-muted);
        margin-top: .1rem;
    }
    .ems-perm-module__badge {
        font-size: .68rem;
        font-weight: 700;
        color: var(--bs-gray-700);
        background: #f1f3f7;
        border-radius: 999px;
        padding: .2rem .55rem;
        flex-shrink: 0;
    }
    .ems-perm-module__badge.has-selected {
        background: var(--bs-primary-light, #f8efcf);
        color: var(--bs-text-primary, #946f11);
    }
    .ems-perm-select-all {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        flex-shrink: 0;
        margin-inline-start: auto;
        padding: .28rem .55rem;
        border: 1px solid var(--ems-border);
        border-radius: 999px;
        background: #fff;
        cursor: pointer;
        user-select: none;
    }
    .ems-perm-select-all span {
        font-size: .72rem;
        font-weight: 700;
        color: var(--ems-text);
        white-space: nowrap;
    }
    .ems-perm-select-all:hover {
        border-color: var(--bs-primary);
        background: var(--bs-primary-light, #f8efcf);
    }
    .ems-perm-select-all .form-check-input {
        margin: 0;
        cursor: pointer;
    }
    .ems-perm-module__chevron {
        color: #a1a5b7;
        font-size: 1.05rem;
        transition: transform .2s ease;
        flex-shrink: 0;
    }
    .ems-perm-module__head:not(.collapsed) .ems-perm-module__chevron {
        transform: rotate(180deg);
    }
    [dir="rtl"] .ems-perm-module__head:not(.collapsed) .ems-perm-module__chevron {
        transform: rotate(-180deg);
    }
    .ems-perm-module__body { border-top: 1px solid var(--ems-border); }
    .ems-perm-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .ems-perm-grid {
        min-width: 36rem;
        padding: .35rem .75rem .75rem;
    }
    .ems-perm-row,
    .ems-perm-cols {
        display: grid;
        grid-template-columns: minmax(12rem, 1.7fr) repeat(5, 4.1rem);
        align-items: center;
        column-gap: .15rem;
    }
    .ems-perm-cols {
        position: sticky;
        top: 0;
        z-index: 1;
        background: #f8f9fb;
        border-radius: .65rem;
        margin: .55rem 0 .25rem;
        padding: .45rem .35rem;
        font-size: .72rem;
        font-weight: 700;
        color: var(--ems-muted);
        letter-spacing: .01em;
    }
    .ems-perm-col {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .25rem;
        white-space: nowrap;
    }
    .ems-perm-row {
        padding: .45rem .35rem;
        border-radius: .55rem;
        min-height: 2.55rem;
    }
    .ems-perm-row:hover { background: #fafbfc; }
    .ems-perm-row.is-hidden { display: none; }
    .ems-perm-row--all {
        background: linear-gradient(90deg, var(--bs-primary-light, #f8efcf) 0%, #fff 58%);
        margin-bottom: .2rem;
    }
    .ems-perm-row--all:hover { background: linear-gradient(90deg, var(--bs-primary-light, #f8efcf) 0%, #fff8e8 58%); }
    .ems-perm-row-title {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        min-width: 0;
        padding-inline-end: .5rem;
        font-size: .84rem;
        font-weight: 600;
        color: var(--ems-text);
    }
    .ems-perm-row-title span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ems-perm-cell {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .2rem;
        min-height: 2rem;
    }
    .ems-perm-cell.is-na .form-check-input {
        display: none;
    }
    .ems-perm-na {
        color: #c4c8d4;
        font-size: .9rem;
        line-height: 1;
        user-select: none;
    }
    .ems-perm-hint {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.15rem;
        height: 1.15rem;
        padding: 0;
        border: 0;
        border-radius: 50%;
        color: var(--bs-primary);
        background: var(--bs-primary-light, rgba(201, 154, 25, .12));
        cursor: help;
        flex-shrink: 0;
        line-height: 1;
        transition: background .15s ease, color .15s ease, transform .15s ease;
    }
    .ems-perm-hint i { font-size: .72rem; }
    .ems-perm-hint:hover,
    .ems-perm-hint:focus {
        background: var(--bs-primary);
        color: #fff;
        transform: translateY(-1px);
        outline: none;
    }
    .ems-perm-popover {
        --bs-popover-max-width: 22rem;
        --bs-popover-border-color: var(--bs-gray-200);
        --bs-popover-header-bg: #fff;
        --bs-popover-body-padding-x: 1rem;
        --bs-popover-body-padding-y: .65rem;
        box-shadow: 0 12px 32px rgba(16, 24, 40, .12);
        border-radius: .75rem;
        font-family: inherit;
    }
    .ems-perm-popover .popover-header {
        font-size: .82rem;
        font-weight: 700;
        color: var(--bs-gray-800);
        border-bottom: 0;
        padding-bottom: .15rem;
    }
    .ems-perm-popover .popover-body {
        font-size: .78rem;
        line-height: 1.65;
        color: var(--bs-gray-600);
        padding-top: 0;
    }
    .modal .ems-perm-shell {
        box-shadow: none;
        border-radius: 12px;
        overflow: visible;
    }
    .modal .ems-perm-list {
        max-height: min(52vh, 32rem);
        overflow: auto;
        overscroll-behavior: contain;
    }
    @media (max-width: 575.98px) {
        .ems-perm-select-all {
            width: 100%;
            justify-content: flex-start;
            margin-inline-start: 0;
        }
    }
    @media (max-width: 767.98px) {
        .ems-perm-shell__head,
        .ems-perm-toolbar,
        .ems-perm-chips { padding-inline: .9rem; }
        .ems-perm-list { padding: .65rem; }
        .ems-perm-grid { min-width: 0; padding-inline: .35rem; }
        .ems-perm-cols { display: none; }
        .ems-perm-row,
        .ems-perm-row--all {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: .45rem;
            padding: .7rem .55rem;
            min-width: 0;
        }
        .ems-perm-row.is-hidden { display: none; }
        .ems-perm-row-title span { white-space: normal; }
        .ems-perm-row-actions {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            width: 100%;
        }
        .ems-perm-cell {
            flex-direction: column;
            gap: .2rem;
        }
        .ems-perm-cell__label {
            display: block;
            font-size: .62rem;
            font-weight: 700;
            color: var(--ems-muted);
            line-height: 1.2;
            text-align: center;
        }
    }
    @media (min-width: 768px) {
        .ems-perm-cell__label { display: none; }
        .ems-perm-row-actions {
            display: contents;
        }
    }
</style>
@endonce

<div class="ems-perm-shell" data-ems-perm-root="1" data-ems-selected-tpl="@lang('employee::permissions.selected_count')">
    <div class="ems-perm-shell__head">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
                @unless ($embedded)
                    <h3 class="ems-perm-shell__title">@lang('employee::main.permissions')</h3>
                @endunless
            </div>
            <span class="ems-perm-total" data-ems-total>—</span>
        </div>
        <div class="ems-perm-legend" data-ems-legend hidden>
            <i class="ki-outline ki-information-5 ems-perm-legend__icon"></i>
            <span class="ems-perm-legend__text">@lang('employee::permissions.legend')</span>
            <button type="button" class="ems-perm-legend__close" data-ems-legend-close
                aria-label="@lang('employee::general.close')">
                <i class="ki-outline ki-cross"></i>
            </button>
        </div>
    </div>

    <div class="ems-perm-toolbar">
        <div class="ems-perm-search">
            <i class="ki-outline ki-magnifier"></i>
            <input type="search" class="form-control form-control-solid" data-ems-perm-search
                placeholder="@lang('employee::permissions.search')" autocomplete="off">
        </div>
        <div class="ems-perm-toolbar__actions">
            <button type="button" class="btn btn-sm btn-light" data-ems-expand-all>
                <i class="ki-outline ki-plus-square fs-5"></i>
                @lang('employee::permissions.expand_all')
            </button>
            <button type="button" class="btn btn-sm btn-light" data-ems-collapse-all>
                <i class="ki-outline ki-minus-square fs-5"></i>
                @lang('employee::permissions.collapse_all')
            </button>
        </div>
    </div>

    <nav class="ems-perm-chips" aria-label="@lang('employee::permissions.modules_nav')">
        @foreach ($modules as $moduleName => $module)
            @php
                $chipLabelKey = "employee::main.{$moduleName}_management_module";
                $chipLabel = __($chipLabelKey);
                $chipLabel = $chipLabel === $chipLabelKey ? $moduleName : $chipLabel;
            @endphp
            <button type="button" class="ems-perm-chip" data-ems-chip="{{ $uid }}-{{ $loop->index }}">
                <span>{{ $chipLabel }}</span>
                <span class="ems-perm-chip__count" data-ems-chip-count>0</span>
            </button>
        @endforeach
    </nav>

    <div class="ems-perm-list" data-ems-list>
        <div class="ems-perm-empty" data-ems-empty>@lang('employee::permissions.no_results')</div>

        @foreach ($modules as $moduleName => $module)
            @php
                $moduleHint = $hint::module($moduleName);
                $labelKey = "employee::main.{$moduleName}_management_module";
                $moduleLabel = __($labelKey);
                $moduleLabel = $moduleLabel === $labelKey ? $moduleName : $moduleLabel;
                $screenCount = $module->keys()
                    ->filter(fn ($key) => $key !== 'all' && ! str_starts_with((string) $key, '_'))
                    ->count();
                $companionAll = $module->get('_screen_module_all');
                $isOpen = $disabled || ($embedded && $moduleIndex === 0);
                $collapseId = $uid.'-'.$moduleIndex;
                $moduleIndex++;
            @endphp
            <article class="ems-perm-module" data-ems-module="{{ $collapseId }}"
                data-ems-haystack="{{ mb_strtolower($moduleLabel) }}">
                <div class="ems-perm-module__head {{ $isOpen ? '' : 'collapsed' }}"
                    data-bs-toggle="collapse"
                    data-bs-target="#ems_mod_{{ $collapseId }}"
                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                    role="button"
                    tabindex="0">
                    <span class="ems-perm-module__icon"><i class="ki-outline ki-abstract-26"></i></span>
                    <span class="ems-perm-module__meta">
                        <span class="ems-perm-module__name">
                            <span>{{ $moduleLabel }}</span>
                            <x-form.permission-hint :title="$moduleHint['title']" :body="$moduleHint['body']" />
                        </span>
                        <span class="ems-perm-module__sub">@lang('employee::permissions.screens_count', ['count' => $screenCount])</span>
                    </span>
                    <span class="ems-perm-module__badge" data-ems-module-count>0</span>
                    @unless ($disabled)
                        <label class="ems-perm-select-all" data-ems-select-all-wrap>
                            <input type="checkbox" class="form-check-input" data-ems-master data-ems-module-select-all>
                            <span>@lang('employee::permissions.select_all')</span>
                        </label>
                    @endunless
                    <i class="ki-outline ki-down ems-perm-module__chevron"></i>
                </div>

                <div id="ems_mod_{{ $collapseId }}" class="collapse ems-perm-module__body {{ $isOpen ? 'show' : '' }}">
                    <div class="ems-perm-scroll">
                        <div class="ems-perm-grid">
                            <div class="ems-perm-cols" aria-hidden="true">
                                <div>@lang('employee::permissions.screen')</div>
                                @foreach ($actions as $action)
                                    @php $col = $hint::column($action); @endphp
                                    <div class="ems-perm-col">
                                        <span>{{ $actionTitles[$action] }}</span>
                                        <x-form.permission-hint :title="$col['title']" :body="$col['body']" placement="bottom" />
                                    </div>
                                @endforeach
                            </div>

                            @if ($module->has('all'))
                                <div class="ems-perm-row ems-perm-row--all" data-ems-row
                                    data-ems-haystack="{{ mb_strtolower($moduleLabel.' '.__('employee::permissions.all_screens')) }}">
                                    <div class="ems-perm-row-title">
                                        <span>@lang('employee::permissions.all_screens')</span>
                                    </div>
                                    <div class="ems-perm-row-actions">
                                        @foreach ($actions as $action)
                                            @php
                                                $allHint = $hint::moduleAll($moduleName, $action);
                                                $allValue = $module['all'][$action] ?? null;
                                            @endphp
                                            <div class="ems-perm-cell {{ $allValue ? '' : 'is-na' }}">
                                                <span class="ems-perm-cell__label">{{ $actionTitles[$action] }}</span>
                                                <x-form.input-div class="form-check form-check-custom form-check-solid mb-0" :row="false">
                                                    <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                        :disabled=$disabled
                                                        value="{{ $allValue }}"
                                                        name="dashboard_permissions[{{ $moduleName }}.all.{{ $action }}]"
                                                        checked="{{ $allValue ? $rolePermissions?->contains($allValue) : false }}"
                                                        :form_control="false"
                                                        attribute="data-select-all={{ $moduleName }}-all-{{ $action }}" />
                                                </x-form.input-div>
                                                @if ($allValue)
                                                    <x-form.permission-hint :title="$allHint['title']" :body="$allHint['body']" />
                                                @else
                                                    <span class="ems-perm-na" title="@lang('employee::permissions.unavailable')">—</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($companionAll)
                                        <div class="d-none" data-ems-companion-group>
                                            @foreach ($actions as $action)
                                                @php $companionValue = $companionAll[$action] ?? null; @endphp
                                                @if ($companionValue)
                                                    <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                        :disabled=$disabled
                                                        value="{{ $companionValue }}"
                                                        name="dashboard_permissions[screen_module.all.{{ $action }}]"
                                                        checked="{{ $rolePermissions?->contains($companionValue) }}"
                                                        :form_control="false"
                                                        attribute="data-ems-companion=1" />
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif

                            @foreach ($module as $key => $permission)
                                @if ($key === 'all' || str_starts_with((string) $key, '_'))
                                    @continue
                                @endif
                                @php
                                    $nameParts = explode('.', (string) $key, 2);
                                    $name_en = $nameParts[0];
                                    $name_ar = $nameParts[1] ?? $nameParts[0];
                                    $entityLabel = session('locale') == 'ar' ? $name_ar : $name_en;
                                    $entityHint = $hint::entity($moduleName, $name_en, $entityLabel);
                                    if ($moduleName === 'screens' && $name_en === 'main' && ($entityHint['title'] ?? '') !== '') {
                                        $entityLabel = $entityHint['title'];
                                    }
                                @endphp
                                <div class="ems-perm-row" data-ems-row
                                    data-ems-haystack="{{ mb_strtolower($moduleLabel.' '.$entityLabel.' '.$name_en.' '.$name_ar) }}">
                                    <div class="ems-perm-row-title">
                                        <span>{{ $entityLabel }}</span>
                                        <x-form.permission-hint :title="$entityHint['title']" :body="$entityHint['body']" />
                                    </div>
                                    <div class="ems-perm-row-actions">
                                        @foreach ($actions as $action)
                                            @php
                                                $isAvailable = $permission->has($action) ? $permission[$action] : null;
                                                $permHint = $isAvailable
                                                    ? $hint::permission($moduleName.'.'.$name_en.'.'.$action, $entityLabel)
                                                    : null;
                                            @endphp
                                            <div class="ems-perm-cell {{ $isAvailable ? '' : 'is-na' }}">
                                                <span class="ems-perm-cell__label">{{ $actionTitles[$action] }}</span>
                                                <x-form.input-div class="fv-row form-check form-check-custom form-check-solid mb-0" :row="false">
                                                    <x-form.input :errors=$errors class="form-check-input" type="checkbox"
                                                        value="{{ $isAvailable }}"
                                                        name="dashboard_permissions[{{ $moduleName . '.' . $name_en . '.' . $action }}]"
                                                        :form_control="false"
                                                        checked="{{ $rolePermissions?->contains($isAvailable) }}"
                                                        disabled="{{ $disabled ? true : ($isAvailable ? false : true) }}" />
                                                </x-form.input-div>
                                                @if ($permHint)
                                                    <x-form.permission-hint :title="$permHint['title']" :body="$permHint['body']" />
                                                @else
                                                    <span class="ems-perm-na" title="@lang('employee::permissions.unavailable')">—</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</div>
