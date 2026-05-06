@extends('layouts.app')

@section('content')
    @php
        $exportListUrl = route('periodic-inventory-list-export-excel', request()->query());
    @endphp
    <div class="container-fluid py-4 periodic-inv-index">
        <div class="card border-0 shadow-sm mb-4 overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div class="d-flex gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-light-primary text-primary"
                            style="width: 56px; height: 56px;">
                            <i class="fa-solid fa-clipboard-list fs-2"></i>
                        </div>
                        <div>
                            <h1 class="h3 mb-1 fw-bold">@lang('accounting::lang.periodic_inventory_log')</h1>
                            <p class="text-muted mb-0 small">@lang('accounting::lang.periodic_inventory_workflow_subtitle')</p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $exportListUrl }}" class="btn btn-export-excel d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-file-excel"></i>
                            <span>@lang('accounting::lang.periodic_export_list_excel')</span>
                        </a>
                        <a href="{{ route('periodic-inventory.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="fa-solid fa-plus"></i>
                            <span>@lang('accounting::lang.new_inventory')</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 g-lg-4 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 periodic-kpi">
                    <div class="card-body">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">@lang('accounting::lang.periods_count')</div>
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="fw-bold fs-2 text-gray-900">{{ (int) ($summary['periods_count'] ?? 0) }}</span>
                            <i class="fa-solid fa-layer-group text-gray-400 fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 periodic-kpi">
                    <div class="card-body">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">@lang('accounting::lang.posted_periods')</div>
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="fw-bold fs-2 text-success">{{ (int) ($summary['posted_count'] ?? 0) }}</span>
                            <i class="fa-solid fa-circle-check text-success fs-3 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 periodic-kpi">
                    <div class="card-body">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">@lang('accounting::lang.periods_without_adjustment')</div>
                        <div class="d-flex align-items-baseline justify-content-between">
                            <span class="fw-bold fs-2 text-warning">{{ (int) ($summary['no_adjustment_count'] ?? 0) }}</span>
                            <i class="fa-solid fa-minus-circle text-warning fs-3 opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 periodic-kpi">
                    <div class="card-body">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">@lang('accounting::lang.cogs_value')</div>
                        <div class="d-flex align-items-baseline justify-content-between gap-2">
                            <span class="fw-bold fs-4 text-gray-900 text-truncate">{{ number_format((float) ($summary['total_cogs'] ?? 0), 2) }}</span>
                            <i class="fa-solid fa-chart-line text-gray-400 fs-3 flex-shrink-0"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-primary d-flex align-items-start gap-2 border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #eef6ff 0%, #f8fafc 100%);">
            <i class="fa-solid fa-circle-info mt-1 text-primary"></i>
            <div class="small mb-0">@lang('accounting::lang.periodic_hint')</div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 pt-4 pb-0 bg-transparent">
                <span class="fw-bold">@lang('accounting::lang.filter')</span>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('periodic-inventory.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label small fw-semibold">@lang('accounting::lang.from_date')</label>
                            <input type="date" name="from_date" class="form-control form-control-solid" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label small fw-semibold">@lang('accounting::lang.to_date')</label>
                            <input type="date" name="to_date" class="form-control form-control-solid" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label small fw-semibold">@lang('accounting::lang.establishment')</label>
                            <select name="establishment" class="form-control form-control-solid">
                                <option value="">@lang('employee::fields.all_establishments')</option>
                                @foreach ($establishments as $establishment)
                                    <option value="{{ $establishment->id }}" @selected(request('establishment') == $establishment->id)>
                                        {{ $establishment->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <label class="form-label small fw-semibold">@lang('accounting::lang.period_status')</label>
                            <select name="status" class="form-control form-control-solid">
                                <option value="">@lang('accounting::lang.all')</option>
                                <option value="with_adjustment" @selected(request('status') === 'with_adjustment')>@lang('accounting::lang.with_adjustment')</option>
                                <option value="without_adjustment" @selected(request('status') === 'without_adjustment')>@lang('accounting::lang.without_adjustment')</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-3 d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1 d-inline-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-filter"></i>
                                <span>@lang('accounting::lang.apply_filter')</span>
                            </button>
                            <a href="{{ route('periodic-inventory.index') }}" class="btn btn-light flex-grow-1">@lang('accounting::lang.reset_filter')</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header border-0 d-flex flex-wrap align-items-center justify-content-between gap-2 pt-5 pb-0 bg-transparent">
                <div class="fw-bold fs-5">@lang('accounting::lang.periodic_inventory_records')</div>
                <a href="{{ $exportListUrl }}" class="btn btn-sm btn-light-success d-inline-flex align-items-center gap-2">
                    <i class="fa-solid fa-file-excel text-success"></i>
                    @lang('accounting::lang.periodic_export_list_excel')
                </a>
            </div>
            <div class="card-body pt-4">
                <div class="table-responsive rounded-3 border">
                    <table class="table table-row-bordered table-row-gray-300 align-middle gs-2 gy-3 mb-0 periodic-inv-table">
                        <thead>
                            <tr class="fw-bold text-gray-800 bg-light">
                                <th class="min-w-100px">@lang('accounting::lang.inventory_number')</th>
                                <th class="min-w-125px">@lang('accounting::lang.period')</th>
                                <th class="min-w-150px">@lang('accounting::lang.establishment_name')</th>
                                <th class="min-w-140px">@lang('accounting::lang.inventory_status')</th>
                                <th class="min-w-120px">@lang('accounting::lang.adjustment_status')</th>
                                <th class="min-w-100px text-end">@lang('accounting::lang.opening_value')</th>
                                <th class="min-w-100px text-end">@lang('accounting::lang.purchases_value')</th>
                                <th class="min-w-100px text-end">@lang('accounting::lang.closing_value')</th>
                                <th class="min-w-100px text-end">@lang('accounting::lang.cogs_value')</th>
                                <th class="min-w-120px">@lang('accounting::lang.created_by_user')</th>
                                <th class="min-w-200px text-end">@lang('accounting::lang.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inventories as $inventory)
                                @php
                                    $hasAdjustmentEntry = ! is_null($inventory->adjustment_entry_id);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge badge-light-dark fs-7">#{{ $inventory->id }}</span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold">
                                            <span class="text-muted">@lang('accounting::lang.periodic_inventory_count_date'):</span> {{ $inventory->end_date }}
                                        </div>
                                    </td>
                                    <td>{{ $inventory->establishment?->name ?? '—' }}</td>
                                    <td>
                                        @if (($inventory->status ?? 'in_review') === 'approved')
                                            <span class="badge badge-light-success">@lang('accounting::lang.approved')</span>
                                        @else
                                            <span class="badge badge-light-warning text-gray-800">@lang('accounting::lang.in_review')</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($hasAdjustmentEntry)
                                            <span class="badge badge-light-success">@lang('accounting::lang.with_adjustment')</span>
                                        @else
                                            <span class="badge badge-light-warning text-gray-800">@lang('accounting::lang.without_adjustment')</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace small">{{ number_format((float) $inventory->opening_stock_value, 2) }}</td>
                                    <td class="text-end font-monospace small">{{ number_format((float) $inventory->purchases_value, 2) }}</td>
                                    <td class="text-end font-monospace small">{{ number_format((float) $inventory->closing_stock_value, 2) }}</td>
                                    <td class="text-end font-monospace fw-semibold">{{ number_format((float) $inventory->cogs, 2) }}</td>
                                    <td class="small">{{ $inventory->creator?->name ?? '—' }}</td>
                                    <td class="text-end">
                                        <a href="#" class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary"
                                            data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                            @lang('employee::fields.actions')
                                            <i class="ki-outline ki-down fs-5 ms-1"></i>
                                        </a>
                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-225px py-4"
                                            data-kt-menu="true">
                                            <div class="menu-item px-3">
                                                <a href="{{ route('periodic-inventory-detail-export-excel', ['id' => $inventory->id]) }}"
                                                    class="menu-link px-3">
                                                    <i class="fa-solid fa-file-excel text-success me-2"></i>
                                                    @lang('accounting::lang.periodic_export_row_excel')
                                                </a>
                                            </div>
                                            @if (($inventory->status ?? 'in_review') === 'approved')
                                                <div class="menu-item px-3">
                                                    <a href="{{ route('periodic-inventory-export-pdf', ['id' => $inventory->id]) }}" class="menu-link px-3">
                                                        <i class="fa-solid fa-file-pdf text-danger me-2"></i>
                                                        @lang('accounting::lang.period_approval_report')
                                                    </a>
                                                </div>
                                            @endif
                                            @if (($inventory->status ?? 'in_review') === 'in_review')
                                                <div class="menu-item px-3">
                                                    <a href="{{ route('periodic-inventory.edit', ['periodic_inventory' => $inventory->id]) }}" class="menu-link px-3">
                                                        <i class="fa-solid fa-pen-to-square text-primary me-2"></i>
                                                        @lang('employee::fields.edit')
                                                    </a>
                                                </div>
                                                <div class="menu-item px-3">
                                                    <form method="POST" action="{{ route('periodic-inventory-approve', ['id' => $inventory->id]) }}"
                                                        onsubmit="return confirm(@json(__('accounting::lang.approve_inventory_confirm')));">
                                                        @csrf
                                                        <button type="submit" class="menu-link px-3 border-0 bg-transparent text-start w-100">
                                                            <i class="fa-solid fa-circle-check text-success me-2"></i>
                                                            @lang('accounting::lang.approve')
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                            @if ($hasAdjustmentEntry)
                                                <div class="menu-item px-3">
                                                    <a href="{{ route('journal-entry-show', ['id' => $inventory->adjustment_entry_id]) }}" class="menu-link px-3">
                                                        <i class="fa-solid fa-eye text-info me-2"></i>
                                                        @lang('accounting::lang.view_journalEntry')
                                                    </a>
                                                </div>
                                                <div class="menu-item px-3">
                                                    <a href="{{ route('journal-entry-print', ['id' => $inventory->adjustment_entry_id]) }}" class="menu-link px-3">
                                                        <i class="fa-solid fa-print text-dark me-2"></i>
                                                        @lang('accounting::lang.print_journalEntry')
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-10">@lang('accounting::lang.no_periodic_inventory_log')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    {{ $inventories->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('css')
    <style>
        .periodic-inv-index .periodic-kpi { transition: transform .15s ease, box-shadow .15s ease; }
        .periodic-inv-index .periodic-kpi:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .06) !important; }
        .periodic-inv-table tbody tr:hover { background-color: rgba(13, 110, 253, .04); }
    </style>
@endsection
