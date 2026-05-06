@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="mb-1">@lang('accounting::lang.new_periodic_inventory')</h1>
                    <div class="text-muted">@lang('accounting::lang.periodic_inventory_workflow_subtitle')</div>
                </div>
                <div class="badge badge-light-info fs-7">@lang('accounting::lang.count_sheet')</div>
            </div>
        </div>

        <form method="POST" action="{{ route('periodic-inventory.store') }}">
            @csrf

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.establishment')</label>
                            <select class="form-control establishment select-2" name="establishment">
                                @foreach ($establishments as $establishment)
                                    <option value="{{ $establishment->id }}">{{ $establishment->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.periodic_inventory_count_date')</label>
                            <input type="date" name="count_date" class="form-control" value="{{ $count_date_default ?? now()->format('Y-m-d') }}" required>
                            <div class="form-text">@lang('accounting::lang.periodic_inventory_count_date_help')</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.periodic_inventory_last_count_hint')</label>
                            <input type="text" class="form-control" value="{{ $start_date }}" readonly>
                        </div>
                    </div>
                </div>
            </div>

            @include('accounting::inventory.periodic.partials.items_table', [
                'products' => $products,
                'itemsByProduct' => collect(),
            ])

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">@lang('accounting::lang.save_as_in_review')</button>
            </div>
        </form>
    </div>
@endsection
