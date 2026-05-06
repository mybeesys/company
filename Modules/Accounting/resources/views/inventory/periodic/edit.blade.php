@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h1 class="mb-1">@lang('accounting::lang.periodic_inventory_log') #{{ $inventory->id }}</h1>
                    <div class="text-muted">@lang('accounting::lang.periodic_inventory_workflow_subtitle')</div>
                </div>
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="badge badge-light-warning fs-7">@lang('accounting::lang.in_review')</span>
                    <form method="POST" action="{{ route('periodic-inventory-approve', ['id' => $inventory->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success" onclick="return confirm(@json(__('accounting::lang.approve_inventory_confirm')));">
                            @lang('accounting::lang.approve_inventory')
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('periodic-inventory.update', ['periodic_inventory' => $inventory->id]) }}">
            @csrf
            @method('PUT')

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.establishment')</label>
                            <select class="form-control establishment select-2" name="establishment">
                                @foreach ($establishments as $establishment)
                                    <option value="{{ $establishment->id }}" @if ($inventory->establishment_id == $establishment->id) selected @endif>
                                        {{ $establishment->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('accounting::lang.periodic_inventory_count_date')</label>
                            <input type="date" name="count_date" class="form-control" value="{{ $inventory->end_date }}" required>
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
                'itemsByProduct' => $itemsByProduct,
            ])

            <div class="mt-4 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                <a href="{{ route('periodic-inventory.index') }}" class="btn btn-light">@lang('accounting::lang.back')</a>
            </div>
        </form>
    </div>
@endsection

