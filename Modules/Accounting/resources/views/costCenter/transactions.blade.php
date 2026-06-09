@extends('layouts.app')

@section('title', __('accounting::lang.cost_center_transactions'))
@section('css')
    <style>
        .empty-content {
            display: grid;
            justify-content: center;
            text-align: center;
            gap: 11px;
            padding-bottom: 37px;
        }

        .cc-report-banner {
            border: 1px solid #e4e6ef;
            border-radius: 12px;
            background: linear-gradient(180deg, #fafbfc 0%, #ffffff 100%);
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
        }

        .cc-report-banner .cc-report-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #181c32;
        }

        .cc-report-banner .cc-report-subtitle {
            font-size: 1rem;
            color: #3f4254;
            margin-top: 0.25rem;
        }

        .cc-report-banner .cc-report-period {
            font-size: 0.9rem;
            color: #7e8299;
            margin-top: 0.35rem;
        }

        #cc-transactions-table {
            border: 1px solid #e4e6ef;
            font-variant-numeric: tabular-nums;
        }

        #cc-transactions-table thead th {
            background: #f5f8fa !important;
            color: #3f4254 !important;
            font-weight: 800;
            text-align: center;
            vertical-align: middle;
        }

        #cc-transactions-table tbody td,
        #cc-transactions-table tfoot td {
            vertical-align: middle;
        }
    </style>
@stop
@section('content')
    <div class="container">
        <div class="cc-report-banner">
            <div class="row align-items-start g-3">
                <div class="col-lg-8">
                    <div class="cc-report-title">@lang('accounting::lang.cost_center_transactions')</div>
                    <div class="cc-report-subtitle">
                        <span class="fw-bold text-gray-800">{{ $costCenter->account_center_number }}</span>
                        <span class="text-gray-500">—</span>
                        {{ app()->getLocale() == 'ar' ? $costCenter->name_ar : $costCenter->name_en }}
                    </div>
                    <div class="cc-report-period">
                        {{ __('accounting::lang.ledger_report_period', [
                            'from' => \Carbon\Carbon::parse($start_date)->format('d/m/Y'),
                            'to' => \Carbon\Carbon::parse($end_date)->format('d/m/Y'),
                        ]) }}
                    </div>
                    @if ($isLeaf)
                        <span class="badge badge-light-success mt-2">@lang('accounting::lang.cost_center_leaf_level')</span>
                    @endif
                </div>
                <div class="col-lg-4">
                    <div class="row g-2 align-items-center justify-content-lg-end">
                        <div class="col-2">
                            @if ($previous)
                                <a href="{{ route('cost-center-transactions', $previous->id) }}?{{ http_build_query($exportQuery) }}"
                                    class="btn btn-primary" style="padding: 5px; border-radius: 50%;">
                                    <i @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-left fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-right fs-1 p-0" @endif></i>
                                </a>
                            @endif
                        </div>
                        <div class="col-8">
                            <select id="costCenters" class="form-select form-select-solid select-2" name="id">
                                @if (! $isLeaf)
                                    <option value="" selected disabled>@lang('accounting::lang.cost_center_transactions_leaf_only')</option>
                                @endif
                                @foreach ($costCenters as $_costCenter)
                                    <option value="{{ $_costCenter->id }}" @if ($isLeaf && $costCenter->id == $_costCenter->id) selected @endif>
                                        {{ $_costCenter->account_center_number }}
                                        —
                                        {{ app()->getLocale() == 'ar' ? $_costCenter->name_ar : $_costCenter->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-2">
                            @if ($next)
                                <a href="{{ route('cost-center-transactions', $next->id) }}?{{ http_build_query($exportQuery) }}"
                                    class="btn btn-primary" style="padding: 5px; border-radius: 50%;">
                                    <i @if (app()->getLocale() == 'en') class="ki-outline ki-arrow-right fs-1 p-0" @endif
                                        @if (app()->getLocale() == 'ar') class="ki-outline ki-arrow-left fs-1 p-0" @endif></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (! $isLeaf)
        <div class="alert alert-warning d-flex align-items-center mb-5">
            <i class="ki-outline ki-information-5 fs-2hx text-warning me-4"></i>
            <div>@lang('accounting::lang.cost_center_transactions_leaf_only')</div>
        </div>
    @endif

    <form action="{{ route('cost-center-transactions', $costCenter->id) }}" method="GET" class="mb-5">
        <div class="row py-3">
            <div class="col-md-3">
                <label>{{ __('accounting::lang.from_date') }}</label>
                <input type="date" name="start_date" value="{{ request('start_date', $start_date) }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label>{{ __('accounting::lang.to_date') }}</label>
                <input type="date" name="end_date" value="{{ request('end_date', $end_date) }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label>{{ __('accounting::lang.transaction_number') }}</label>
                <input type="text" name="ref_no" class="form-control" value="{{ request('ref_no') }}"
                    placeholder="{{ __('accounting::lang.transaction_number') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    {{ app()->getLocale() == 'ar' ? 'بحث' : 'Search' }}
                </button>
            </div>
        </div>
    </form>

    <div class="card mb-5 mb-xl-8" @if (app()->getLocale() == 'ar') dir="rtl" @endif>
        <div class="card-header border-0 pt-5">
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.cost_center_transactions')</span>
                <span class="text-muted mt-1 fw-semibold fs-7">
                    @if ($transactions->count() > 0)
                        {{ $transactions->count() }} @lang('messages.transactions')
                    @endif
                </span>
            </h3>
            @if ($isLeaf && $transactions->count() > 0)
                @php $exportQs = http_build_query($exportQuery); @endphp
                <div class="card-toolbar">
                    <div class="btn-group dropend">
                        <button type="button" style="background: transparent; border-radius: 6px;"
                            class="btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog" style="font-size: 1.4rem; color: #c59a00;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu" style="width: max-content; padding: 10px;">
                            <li class="mb-5"
                                style="text-align: justify; border-bottom: 1px solid #00000029; padding: 0.8rem;">
                                <span class="card-label fw-bold fs-6 mb-1">@lang('messages.settings')</span>
                            </li>
                            <li>
                                <div class="menu-item-custom">
                                    <a href="{{ url('/cost-center-transactions-print', $costCenter->id) }}?{{ $exportQs }}"
                                        class="btn">@lang('accounting::fields.print')</a>
                                </div>
                            </li>
                            <li>
                                <div class="menu-item-custom">
                                    <a href="{{ url('/cost-center-transactions-export-pdf', $costCenter->id) }}?{{ $exportQs }}"
                                        class="btn btn-export-pdf">@lang('general.export_as_pdf')</a>
                                </div>
                            </li>
                            <li>
                                <div class="menu-item-custom">
                                    <a href="{{ url('/cost-center-transactions-export-excel', $costCenter->id) }}?{{ $exportQs }}"
                                        class="btn btn-export-excel">@lang('general.export_as_excel')</a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        @if (! $isLeaf || $transactions->count() == 0)
            <div class="empty-content">
                <img src="/assets/media/illustrations/empty-content.svg" class="w-200px" alt="">
                <span class="text-gray-500 fw-semibold fs-6" style="margin: 7px -34px;">
                    @if (! $isLeaf)
                        @lang('accounting::lang.cost_center_transactions_leaf_only')
                    @else
                        @lang('accounting::lang.no_cost_center_transactions')
                    @endif
                </span>
            </div>
        @else
            <div class="card-body py-3">
                <div class="table-responsive">
                    <table class="table align-middle gs-0 gy-4" id="cc-transactions-table">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="min-w-125px">@lang('accounting::lang.transaction_number')</th>
                                <th class="min-w-90px">@lang('accounting::lang.operation_date')</th>
                                <th class="min-w-175px">@lang('accounting::lang.account_name')</th>
                                <th class="min-w-200px">@lang('accounting::lang.ledger_narration')</th>
                                <th class="min-w-125px">@lang('accounting::lang.added_by')</th>
                                <th class="min-w-100px text-end">@lang('accounting::lang.debit')</th>
                                <th class="min-w-100px text-end">@lang('accounting::lang.credit')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @include('accounting::costCenter.partials.transaction_rows', ['transactions' => $transactions])
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold bg-light">
                                <td colspan="5" class="text-end">@lang('accounting::lang.total')</td>
                                <td class="text-end">{{ number_format($totalDebit, 2) }}</td>
                                <td class="text-end">{{ number_format($totalCredit, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            $('#costCenters').on('change', function() {
                var selectedValue = this.value;
                var params = new URLSearchParams(@json($exportQuery));
                var url = '{{ url('cost-center-transactions') }}/' + selectedValue;
                var qs = params.toString();
                window.location.href = qs ? url + '?' + qs : url;
            });

            $('#costCenters').select2();
        });
    </script>
@stop
