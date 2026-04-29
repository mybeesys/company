@extends('layouts.app')

@section('title', __('accounting::lang.customers_and_suppliers_statement_of_account_report'))

@section('content')
    <section class="content-header py-3">
        <h1 class="card-title ">
            {{ __('accounting::lang.customers_and_suppliers_statement_of_account_report') }}
            -
            @if (Lang::has('accounting::lang.' . $contact->name))
                @lang('accounting::lang.' . $contact->name) ({{ $contact->contact_id }})
            @else
                {{ $contact->name }}
            @endif
        </h1>
    </section>

    <div class="">
        {{-- <div class="card shadow-sm mb-5">
            <div class="card-body">
                <h2 class="card-title text-primary">
                    {{ __('accounting::lang.customers_and_suppliers_statement_of_account_report') }}
                    -
                    @if (Lang::has('accounting::lang.' . $contact->name))
                        @lang('accounting::lang.' . $contact->name) ({{ $contact->contact_id }})
                    @else
                        {{ $contact->name }}
                    @endif
                </h2>
            </div>
        </div> --}}
        <hr>


        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    @lang('accounting::lang.name'): {{ $contact->name }}
                </h5>
                <h5 class="card-title mb-0">
                    @lang('accounting::lang.balance'): @format_currency($current_bal)
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('customers-suppliers-statement') }}">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.cs') }}</label>
                            <select name="id" id="contact_filter" class="form-select select-2">
                                @foreach ($contact_dropdown as $client)
                                    <option value="{{ $client->id }}" @selected($contact_id == $client->id)>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.from_date') }}</label>
                            <input type="date" name="start_date" id="start_date_filter" class="form-control"
                                value="{{ request()->start_date ?? now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.to_date') }}</label>
                            <input type="date" name="end_date" id="end_date_filter" class="form-control"
                                value="{{ request()->end_date ?? now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="choose_cost_center_select">{{ __('accounting::lang.cost_center') }}:</label>
                                <select name="choose_cost_center_select[]" id="choose_cost_center_select"
                                    class="form-select d-flex form-select-solid" multiple>
                                    @foreach ($costCenters as $costCenter)
                                        <option value="{{ $costCenter->id }}"
                                            @if (in_array($costCenter->id, $choose_cost_center_select ?? [])) selected @endif>
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
                            <label class="form-label">{{ __('accounting::lang.movement_type') }}</label>
                            <select name="entry_type" id="entry_type_filter" class="form-select select-2">
                                <option value="">@lang('messages.select')</option>
                                <option value="debit" @selected(($entry_type ?? '') === 'debit')>@lang('accounting::lang.debit')</option>
                                <option value="credit" @selected(($entry_type ?? '') === 'credit')>@lang('accounting::lang.credit')</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.balance') }}</label>
                            <select name="balance_side" id="balance_side_filter" class="form-select select-2">
                                <option value="" @selected(empty($balance_side))>الكل</option>
                                <option value="debit" @selected(($balance_side ?? '') === 'debit')>حركات مدينة فقط</option>
                                <option value="credit" @selected(($balance_side ?? '') === 'credit')>حركات دائنة فقط</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.transaction_type') }}</label>
                            <select name="sub_type" id="sub_type_filter" class="form-select select-2">
                                <option value="">@lang('messages.select')</option>
                                @foreach ($available_sub_types as $availableSubType)
                                    <option value="{{ $availableSubType }}" @selected(($sub_type ?? '') === $availableSubType)>
                                        {{ Lang::has('accounting::lang.' . $availableSubType) ? __('accounting::lang.' . $availableSubType) : $availableSubType }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('accounting::lang.transaction_number') }}</label>
                            <input type="text" name="ref_no" id="ref_no_filter" class="form-control"
                                value="{{ $ref_no ?? '' }}" placeholder="{{ __('accounting::lang.transaction_number') }}">
                        </div>

                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('report::general.filter') }}</button>
                            <button type="button" id="statementExportPdf" class="btn btn-light-primary">PDF</button>
                            <button type="button" id="statementExportExcel" class="btn btn-light-success">Excel</button>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="alert alert-light-primary mb-0">
                    <strong>@lang('accounting::lang.debit'):</strong> @format_currency($period_debit ?? 0)
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-light-success mb-0">
                    <strong>@lang('accounting::lang.credit'):</strong> @format_currency($period_credit ?? 0)
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert {{ ($net_movement ?? 0) >= 0 ? 'alert-light-info' : 'alert-light-warning' }} mb-0">
                    <strong>@lang('accounting::lang.difference'):</strong> @format_currency($net_movement ?? 0)
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <h4>{{ __('accounting::lang.statement_entries') }}</h4>
                <div class="table-responsive">
                    <table class="table align-middle table-striped table-row-bordered fs-6 gy-5" id="ledger">
                        <thead>
                            <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0 w-100">
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.number')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.operation_date')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.transaction')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.cost_center')</th>
                                <th class="min-w-75px text-start align-middle">@lang('employee::general.notes')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.added_by')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.debit')</th>
                                <th class="min-w-75px text-start align-middle">@lang('accounting::lang.credit')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="text-start text-gray-600 fw-bold  text-uppercase gs-0 w-100">
                                <td colspan="6">@lang('report::general.filter')</td>
                                <td class="footer_total_debit min-w-75px text-start align-middle"></td>
                                <td class="footer_total_credit min-w-75px text-start align-middle"></td>
                            </tr>
                            <tr class="text-start text-gray-600 fw-bold  text-uppercase gs-0 w-100">
                                <td colspan="6">@lang('accounting::lang.total')</td>
                                <td class="footer_final_total_debit min-w-75px text-start align-middle"></td>
                                <td class="footer_final_total_credit min-w-75px text-start align-middle"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent

    <script src="{{ url('js/table.js') }}"></script>

    <script>
        "use strict";

        const statementExportPdfUrl = '{{ route('customers-suppliers-statement-export-pdf') }}';
        const statementExportExcelUrl = '{{ route('customers-suppliers-statement-export-excel') }}';

        function buildStatementQuery() {
            const params = new URLSearchParams();
            const startDate = $('#start_date_filter').val();
            const endDate = $('#end_date_filter').val();
            const contactId = $('#contact_filter').val();
            const costCenters = $('#choose_cost_center_select').val() || [];
            const entryType = $('#entry_type_filter').val();
            const balanceSide = $('#balance_side_filter').val();
            const subType = $('#sub_type_filter').val();
            const refNo = $('#ref_no_filter').val();

            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            if (contactId) params.append('id', contactId);
            if (entryType) params.append('entry_type', entryType);
            if (balanceSide) params.append('balance_side', balanceSide);
            if (subType) params.append('sub_type', subType);
            if (refNo) params.append('ref_no', refNo);
            costCenters.forEach(function(value) {
                params.append('choose_cost_center_select[]', value);
            });
            return params.toString();
        }



        $(document).ready(function() {
            $('#choose_cost_center_select').select2();

            $('#contact_filter').select2();
            $('#entry_type_filter').select2();
            $('#balance_side_filter').select2();
            $('#sub_type_filter').select2();
            let ledger;

            $('#contact_filter').change(function() {
                const contact_id = $(this).val();
                const url = '{{ route('customers-suppliers-statement', ['id' => 'CONTACT_ID']) }}'.replace(
                    'CONTACT_ID', contact_id);
                window.location = url;
            });

            ledger = $('#ledger').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('customers-suppliers-statement', $contact->id) }}',
                    data: function(d) {
                        d.start_date = $('#start_date_filter').val();
                        d.end_date = $('#end_date_filter').val();
                        d.id = $('#contact_filter').val();
                        d.choose_cost_center_select = $('#choose_cost_center_select').val();
                        d.entry_type = $('#entry_type_filter').val();
                        d.balance_side = $('#balance_side_filter').val();
                        d.sub_type = $('#sub_type_filter').val();
                        d.ref_no = $('#ref_no_filter').val();
                    }
                },
                columns: [{
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'operation_date',
                        name: 'operation_date'
                    },
                    {
                        data: 'transaction',
                        name: 'transaction'
                    },
                    {
                        data: 'cost_center_name',
                        name: 'cost_center_name'
                    },
                    {
                        data: 'note',
                        name: 'ATM.note'
                    },
                    {
                        data: 'added_by',
                        name: 'added_by'
                    },
                    {
                        data: 'debit',
                        name: 'amount',
                        searchable: false
                    },
                    {
                        data: 'credit',
                        name: 'amount',
                        searchable: false
                    }
                ],
                fnDrawCallback: function() {
                    __currency_convert_recursively($('#ledger'));
                },
                order: [],
                scrollX: true,
                pageLength: 10,
                drawCallback: function() {
                    KTMenu.createInstances();
                },
                footerCallback: function(row, data) {
                    let totalDebit = 0,
                        totalCredit = 0;
                    data.forEach(row => {
                        totalDebit += $(row.debit).data('orig-value') ? parseFloat($(row.debit)
                            .data('orig-value')) : 0;
                        totalCredit += $(row.credit).data('orig-value') ? parseFloat($(row
                            .credit).data('orig-value')) : 0;
                    });
                    $('.footer_total_debit').html((totalDebit));
                    $('.footer_total_credit').html((totalCredit));
                    const response = this.api().ajax.json() || {};
                    $('.footer_final_total_debit').html(response.period_debit ?? {{ (float) $total_debit_bal }});
                    $('.footer_final_total_credit').html(response.period_credit ?? {{ (float) $total_credit_bal }});
                }
            });

            $('#statementExportPdf').on('click', function() {
                const query = buildStatementQuery();
                window.open(statementExportPdfUrl + '?' + query, '_blank');
            });

            $('#statementExportExcel').on('click', function() {
                const query = buildStatementQuery();
                window.location.href = statementExportExcelUrl + '?' + query;
            });

        });
    </script>
@endsection
