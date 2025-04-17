@extends('layouts.app')

@section('title', __('accounting::lang.customers_and_suppliers_statement_of_account_report'))

@section('content')
    <div class="">
        <div class="card shadow-sm mb-5">
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
        </div>
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
                        <div class="col-md-4">
                            <label class="form-label">{{ __('accounting::lang.cs') }}</label>
                            <select name="contact_id" id="contact_filter" class="form-select select-2">
                                @foreach ($contact_dropdown as $client)
                                    <option value="{{ $client->id }}" @selected($contact_id == $client->id)>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('accounting::lang.from_date') }}</label>
                            <input type="date" name="start_date" id="start_date_filter" class="form-control"
                                value="{{ request()->start_date ?? now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('accounting::lang.to_date') }}</label>
                            <input type="date" name="end_date" id="end_date_filter" class="form-control"
                                value="{{ request()->end_date ?? now()->format('Y-m-d') }}">
                        </div>
                    </div>
                </form>
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
                                <td colspan="6">@lang('sales::lang.total_before_vat')</td>
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
        $(document).ready(function() {
            $('#contact_filter').select2();
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
                    $('.footer_final_total_debit').html(({{ $total_debit_bal }}));
                    $('.footer_final_total_credit').html(({{ $total_credit_bal }}));
                }
            });

            $('#start_date_filter, #end_date_filter').on('change', function() {
                ledger.ajax.reload();
            });
        });
    </script>
@endsection
