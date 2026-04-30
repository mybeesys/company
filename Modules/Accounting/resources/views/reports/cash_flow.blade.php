@extends('layouts.app')

@section('title', __('accounting::lang.cash_flow_statement'))

@section('content')
    <div class="container">
        <h2 class="mb-4">{{ __('accounting::lang.cash_flow_statement') }}</h2>
        @include('accounting::reports.partials.inventory_policy_notice')

        <form action="{{ route('cash-flow') }}" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <label>{{ __('accounting::lang.from_date') }}</label>
                    <input type="date" name="start_date" value="{{ request('start_date', $startDate) }}"
                        class="form-control">
                </div>
                <div class="col-md-3">
                    <label>{{ __('accounting::lang.to_date') }}</label>
                    <input type="date" name="end_date" value="{{ request('end_date', $endDate) }}" class="form-control">
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}:</label>
                        <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                            class="form-select d-flex form-select-solid" multiple>
                            @foreach ($costCenters as $costCenter)
                                <option value="{{ $costCenter->id }}" @if (in_array($costCenter->id, $choose_cost_center_select ?? [])) selected @endif>
                                    @if (app()->getLocale() == 'ar')
                                        {{ $costCenter->account_center_number . ' - ' . $costCenter->name_ar }}
                                    @else
                                        {{ $costCenter->account_center_number . ' - ' . $costCenter->name_en }}
                                    @endif

                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-3">
                    <label for="movement_type">{{ __('accounting::lang.movement_type') }}</label>
                    <select name="movement_type" id="movement_type" class="form-control">
                        <option value="">@lang('messages.select')</option>
                        <option value="credit" @selected(($movement_type ?? '') === 'credit')>@lang('accounting::lang.credit')</option>
                        <option value="debit" @selected(($movement_type ?? '') === 'debit')>@lang('accounting::lang.debit')</option>
                    </select>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-6">
                    <label for="sub_types">{{ __('accounting::lang.transaction_type') }}</label>
                    <select name="sub_types[]" id="sub_types" class="form-select d-flex form-select-solid" multiple>
                        @foreach ($availableSubTypes as $subType)
                            <option value="{{ $subType }}" @if (in_array($subType, $selected_sub_types ?? [])) selected @endif>
                                {{ \Illuminate\Support\Facades\Lang::has('accounting::lang.' . $subType) ? __('accounting::lang.' . $subType) : $subType }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="activity_section">{{ __('accounting::lang.activity_section') }}</label>
                    <select name="activity_section" id="activity_section" class="form-control">
                        <option value="">@lang('messages.select')</option>
                        <option value="operating" @selected(($activity_section ?? '') === 'operating')>@lang('accounting::lang.operating_activities')</option>
                        <option value="investing" @selected(($activity_section ?? '') === 'investing')>@lang('accounting::lang.investing_activities')</option>
                        <option value="financing" @selected(($activity_section ?? '') === 'financing')>@lang('accounting::lang.financing_activities')</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('report::general.filter') }}</button>
                    <button type="button" id="cashFlowExportPdf" class="btn btn-light-primary">PDF</button>
                    <button type="button" id="cashFlowExportExcel" class="btn btn-light-success">Excel</button>
                </div>
            </div>
        </form>

        <hr>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="alert alert-success mb-0">
                    <strong>{{ __('accounting::lang.cash_inflows') }}:</strong>
                    {{ number_format($cashInflows, 2) }} @get_format_currency()
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-danger mb-0">
                    <strong>{{ __('accounting::lang.cash_outflows') }}:</strong>
                    {{ number_format($cashOutflows, 2) }} @get_format_currency()
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert {{ $netCashFlow >= 0 ? 'alert-primary' : 'alert-warning' }} mb-0">
                    <strong>{{ __('accounting::lang.net_cash_flows') }}:</strong>
                    {{ number_format($netCashFlow, 2) }} @get_format_currency()
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 bg-light-success">
                    <div class="card-body py-3">
                        <div class="fw-semibold">{{ __('accounting::lang.operating_activities') }}</div>
                        <div class="fs-5 fw-bold">{{ number_format($sectionSummaries['operating']['net'] ?? 0, 2) }} @get_format_currency()</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light-warning">
                    <div class="card-body py-3">
                        <div class="fw-semibold">{{ __('accounting::lang.investing_activities') }}</div>
                        <div class="fs-5 fw-bold">{{ number_format($sectionSummaries['investing']['net'] ?? 0, 2) }} @get_format_currency()</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light-primary">
                    <div class="card-body py-3">
                        <div class="fw-semibold">{{ __('accounting::lang.financing_activities') }}</div>
                        <div class="fs-5 fw-bold">{{ number_format($sectionSummaries['financing']['net'] ?? 0, 2) }} @get_format_currency()</div>
                    </div>
                </div>
            </div>
        </div>

        <h4>{{ __('accounting::lang.cash_flow_statement') }}</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('accounting::lang.activity_section') }}</th>
                    <th>{{ __('accounting::lang.date') }}</th>
                    <th>{{ __('accounting::lang.transaction_number') }}</th>
                    <th>{{ __('accounting::lang.transaction_type') }}</th>
                    <th>{{ __('accounting::lang.cost_center') }}</th>
                    <th>{{ __('accounting::lang.amount') }}</th>
                    <th>{{ __('accounting::lang.movement_type') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($operatingCashFlows as $key => $flow)
                    <tr>
                        <td>{{ $operatingCashFlows->firstItem() + $key }}</td>
                        <td>@lang('accounting::lang.' . $flow->section_key . '_activities')</td>
                        <td>{{ \Carbon\Carbon::parse($flow->operation_date)->translatedFormat('Y-m-d h:i:s A') }}</td>
                        <td>{{ $flow->accTransMapping?->ref_no ?? '--' }}</td>
                        <td>{{ \Illuminate\Support\Facades\Lang::has('accounting::lang.' . $flow->sub_type) ? __('accounting::lang.' . $flow->sub_type) : $flow->sub_type }}</td>
                        <td>
                            @if ($flow->costCenter)
                                {{ app()->getLocale() == 'ar' ? ($flow->costCenter->name_ar ?? $flow->costCenter->name_en) : ($flow->costCenter->name_en ?? $flow->costCenter->name_ar) }}
                            @else
                                --
                            @endif
                        </td>
                        <td class="text-{{ $flow->type == 'debit' ? 'danger' : 'success' }}">
                            {{ number_format($flow->amount, 2) }} @get_format_currency()
                        </td>
                        <td>{{ $flow->type == 'debit' ? __('accounting::lang.debit') : __('accounting::lang.credit') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">@lang('messages.no_data_found')</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="d-flex justify-content-center">
            <nav>
                <ul class="pagination">
                    {{ $operatingCashFlows->appends(request()->query())->links('pagination::bootstrap-4') }}
                </ul>
            </nav>
        </div>


        <div class="alert alert-success" role="alert">
            <strong>{{ __('accounting::lang.income') }}</strong> {{ __('accounting::lang.income_description') }}
        </div>

        <div class="alert alert-danger" role="alert">
            <strong>{{ __('accounting::lang.expenses') }}</strong> {{ __('accounting::lang.expenses_description') }}
        </div>

    </div>
@endsection


@section('script')

    <script>
        const cashFlowExportPdfUrl = '{{ route('cash-flow-export-pdf') }}';
        const cashFlowExportExcelUrl = '{{ route('cash-flow-export-excel') }}';

        function buildCashFlowQuery() {
            const params = new URLSearchParams();
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            const movementType = $('#movement_type').val();
            const activitySection = $('#activity_section').val();
            const costCenters = $('#choose_cost_center_select').val() || [];
            const subTypes = $('#sub_types').val() || [];

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (movementType) params.append('movement_type', movementType);
            if (activitySection) params.append('activity_section', activitySection);
            costCenters.forEach(function(value) {
                params.append('choose_cost_center_select[]', value);
            });
            subTypes.forEach(function(value) {
                params.append('sub_types[]', value);
            });
            return params.toString();
        }

        $(document).ready(function() {
            $('#choose_cost_center_select').select2();
            $('#movement_type').select2();
            $('#sub_types').select2();
            $('#activity_section').select2();

            $('#cashFlowExportPdf').on('click', function() {
                const query = buildCashFlowQuery();
                window.open(cashFlowExportPdfUrl + '?' + query, '_blank');
            });

            $('#cashFlowExportExcel').on('click', function() {
                const query = buildCashFlowQuery();
                window.location.href = cashFlowExportExcelUrl + '?' + query;
            });

        });
    </script>
@endsection
