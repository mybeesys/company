@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-6 my-1">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    <h1> @lang('accounting::lang.periodic_inventory_log')</h1>

                </div>
            </div>
            <div class="col-6 my-3" style="justify-content: end;display: flex;">
                <a href="{{ route('periodic-inventory.create') }}" class="btn btn-flex btn-primary h-40px fs-7 fw-bold">
                    @lang('accounting::lang.new_inventory')
                </a>
            </div>
        </div>
    </div>

    <div class="container">

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('periodic-inventory.index') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('accounting::lang.from_date')</label>
                                <input type="date" name="from_date" class="form-control"
                                    value="{{ request('from_date') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('accounting::lang.to_date')</label>
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>@lang('accounting::lang.establishment')</label>
                                <select name="establishment" class="form-control">
                                    <option value="">@lang('employee::fields.all_establishments')</option>
                                    @foreach ($establishments as $establishment)
                                        <option value="{{ $establishment->id }}"
                                            @if (request('establishment') == $establishment->id) selected @endif>
                                            {{ $establishment->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary mr-2 mx-2">
                                <i class="fas fa-filter mr-2"></i>
                            </button>
                            <a href="{{ route('periodic-inventory.index') }}" class="btn btn-secondary">
                                <i class="fas fa-undo-alt mr-1"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>@lang('accounting::lang.inventory_number')</th>
                            <th>@lang('accounting::lang.period')</th>
                            <th>@lang('accounting::lang.opening_value')</th>
                            <th>@lang('accounting::lang.purchases_value')</th>
                            <th>@lang('accounting::lang.closing_value')</th>
                            <th>@lang('accounting::lang.cogs_value')</th>
                            <th>@lang('accounting::lang.actions')</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if (count($inventories) == 0)
                            <tr>
                                <td colspan="7" class="text-center">@lang('accounting::lang.no_periodic_inventory_log')</td>
                            </tr>
                        @endif
                        @foreach ($inventories as $inventory)
                            <tr>
                                <td>{{ $inventory->id }}</td>
                                <td>
                                    {{ $inventory->start_date }}
                                    @lang('accounting::lang.to')
                                    {{ $inventory->end_date }}
                                </td>
                                <td>{{ number_format($inventory->opening_stock_value, 2) }}</td>
                                <td>{{ number_format($inventory->purchases_value, 2) }}</td>
                                <td>{{ number_format($inventory->closing_stock_value, 2) }}</td>
                                <td>{{ number_format($inventory->cogs, 2) }}</td>
                                {{-- <td>
                                    <a href="{{ route('periodic-inventory.show', $inventory->id) }}"
                                        class="btn btn-sm btn-info" title="@lang('accounting::lang.view')">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td> --}}
                                <td><a href="#"
                                        class="btn btn-sm btn-light btn-flex btn-center btn-active-light-primary show menu-dropdown"
                                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions<i
                                            class="ki-outline ki-down fs-5 ms-1"></i></a>
                                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-1 "
                                        data-kt-menu="true"
                                        style="z-index: 107; position: fixed; inset: 0px 0px auto auto; margin: 0px; transform: translate(-102px, 103px);"
                                        data-popper-placement="bottom-end">
                                        <div class="menu-item px-1">
                                            <a href="#" class="menu-link px-3">@lang('employee::general.view')</a>
                                            <a href="#" class="menu-link px-3">@lang('general.print')</a>
                                            <a href="#" class="menu-link px-3">@lang('screen::general.delete')</a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>


                <div class="d-flex justify-content-center">
                    <nav>
                        <ul class="pagination">
                            {{ $inventories->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </ul>
                    </nav>
                </div>


            </div>
        </div>
    </div>
@endsection
