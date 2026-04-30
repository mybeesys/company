@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-4">
                <div>
                    <h1 class="mb-1">@lang('accounting::lang.periodic_inventory_log')</h1>
                    <div class="text-muted">@lang('accounting::lang.periodic_inventory_workflow_subtitle')</div>
                </div>
                <a href="{{ route('periodic-inventory.create') }}" class="btn btn-primary btn-sm">
                    @lang('accounting::lang.new_inventory')
                </a>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7">@lang('accounting::lang.periods_count')</div>
                        <div class="fw-bold fs-2">{{ (int) ($summary['periods_count'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7">@lang('accounting::lang.posted_periods')</div>
                        <div class="fw-bold fs-2 text-success">{{ (int) ($summary['posted_count'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7">@lang('accounting::lang.periods_without_adjustment')</div>
                        <div class="fw-bold fs-2 text-warning">{{ (int) ($summary['no_adjustment_count'] ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted fs-7">@lang('accounting::lang.cogs_value')</div>
                        <div class="fw-bold fs-2">{{ number_format((float) ($summary['total_cogs'] ?? 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mb-4">@lang('accounting::lang.periodic_hint')</div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('periodic-inventory.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label">@lang('accounting::lang.from_date')</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">@lang('accounting::lang.to_date')</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">@lang('accounting::lang.establishment')</label>
                            <select name="establishment" class="form-control">
                                <option value="">@lang('employee::fields.all_establishments')</option>
                                @foreach ($establishments as $establishment)
                                    <option value="{{ $establishment->id }}" @selected(request('establishment') == $establishment->id)>
                                        {{ $establishment->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">@lang('accounting::lang.period_status')</label>
                            <select name="status" class="form-control">
                                <option value="">@lang('accounting::lang.all')</option>
                                <option value="with_adjustment" @selected(request('status') === 'with_adjustment')>@lang('accounting::lang.with_adjustment')</option>
                                <option value="without_adjustment" @selected(request('status') === 'without_adjustment')>@lang('accounting::lang.without_adjustment')</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                <i class="fas fa-filter"></i>
                                <span>@lang('accounting::lang.apply_filter')</span>
                            </button>
                        </div>
                        <div class="col-md-1 d-flex gap-2">
                            <a href="{{ route('periodic-inventory.index') }}" class="btn btn-light w-100">@lang('accounting::lang.reset_filter')</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead>
                            <tr>
                                <th>@lang('accounting::lang.inventory_number')</th>
                                <th>@lang('accounting::lang.period')</th>
                                <th>@lang('accounting::lang.establishment_name')</th>
                                <th>@lang('accounting::lang.period_status')</th>
                                <th>@lang('accounting::lang.opening_value')</th>
                                <th>@lang('accounting::lang.purchases_value')</th>
                                <th>@lang('accounting::lang.closing_value')</th>
                                <th>@lang('accounting::lang.cogs_value')</th>
                                <th>@lang('accounting::lang.created_by_user')</th>
                                <th>@lang('accounting::lang.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($inventories as $inventory)
                                @php
                                    $hasAdjustmentEntry = !is_null($inventory->adjustment_entry_id);
                                @endphp
                                <tr>
                                    <td>#{{ $inventory->id }}</td>
                                    <td>{{ $inventory->start_date }} @lang('accounting::lang.to') {{ $inventory->end_date }}</td>
                                    <td>{{ $inventory->establishment?->name ?? '--' }}</td>
                                    <td>
                                        <span class="badge badge-light-success">
                                            @lang('accounting::lang.approved')
                                        </span>
                                    </td>
                                    <td>{{ number_format((float) $inventory->opening_stock_value, 2) }}</td>
                                    <td>{{ number_format((float) $inventory->purchases_value, 2) }}</td>
                                    <td>{{ number_format((float) $inventory->closing_stock_value, 2) }}</td>
                                    <td>{{ number_format((float) $inventory->cogs, 2) }}</td>
                                    <td>{{ $inventory->creator?->name ?? '--' }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <a href="{{ route('periodic-inventory-export-pdf', ['id' => $inventory->id]) }}" class="btn btn-sm btn-light-secondary">
                                                @lang('accounting::lang.period_approval_report')
                                            </a>
                                            @if ($hasAdjustmentEntry)
                                                <a href="{{ route('journal-entry-show', ['id' => $inventory->adjustment_entry_id]) }}" class="btn btn-sm btn-light-primary">
                                                    @lang('accounting::lang.view_journalEntry')
                                                </a>
                                                <a href="{{ route('journal-entry-print', ['id' => $inventory->adjustment_entry_id]) }}" class="btn btn-sm btn-light-info">
                                                    @lang('accounting::lang.print_journalEntry')
                                                </a>
                                            @else
                                                <span class="badge badge-light-info">@lang('accounting::lang.no_adjustment_needed')</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">@lang('accounting::lang.no_periodic_inventory_log')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $inventories->appends(request()->query())->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
@endsection
