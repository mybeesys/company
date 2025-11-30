@extends('layouts.app')
@section('title', __('report::fields.register_report'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('report::fields.register_report') }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-solid">
                <div class="box-header">
                    <h3 class="box-title">{{ __('report::fields.filters') }}</h3>
                </div>
                <div class="box-body">
                    <form id="register_report_filter_form" method="GET"
                        action="{{ action([Modules\Report\Http\Controllers\SalesReportController::class, 'getRegisterReport']) }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="register_user_id">{{ __('report::fields.user') }}:</label>
                                    <select name="register_user_id" id="register_user_id" class="form-control select2"
                                        style="width:100%">
                                        <option value="">{{ __('report::fields.all_users') }}</option>
                                        @foreach ($users as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="register_status">{{ __('report::fields.status') }}:</label>
                                    <select name="register_status" id="register_status" class="form-control select2"
                                        style="width:100%">
                                        <option value="">{{ __('accounting::lang.all') }}</option>
                                        <option value="open">{{ __('report::fields.open') }}</option>
                                        <option value="close">{{ __('report::fields.close') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary my-10">
                <div class="box-body">
                    <table class="table align-middle table-striped table-row-bordered fs-6 gy-5" id="register_report_table">
                        <thead>
                            <tr class="text-start text-gray-600 fw-bold fs-7 text-uppercase gs-0 w-100">
                                <th class="min-w-10px p-3 align-middle no-border">@lang('report::fields.open_time')</th>
                                <th class="min-w-10px p-3 align-middle no-border">@lang('report::fields.close_time')</th>
                                <th class="min-w-10px p-3 align-middle no-border">@lang('report::fields.location')</th>
                                <th class="min-w-10px p-3 align-middle no-border">@lang('report::fields.user')</th>
                                <th class="min-w-10px p-3 align-middle no-border">@lang('report::fields.total_card_slips')</th>
                                <th>@lang('report::fields.total_cheques')</th>
                                <th>@lang('report::fields.total_cash')</th>
                                <th>@lang('report::fields.total_bank_transfer')</th>
                                <th>@lang('report::fields.total')</th>
                                <th>@lang('messages.actions')</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr class="bg-gray font-17 text-center footer-total">
                                <td colspan="4"><strong>@lang('report::fields.total'):</strong></td>
                                <td class="footer_total_card_payment"></td>
                                <td class="footer_total_cheque_payment"></td>
                                <td class="footer_total_cash_payment"></td>
                                <td class="footer_total_bank_transfer_payment"></td>
                                <td class="footer_total"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade view_register" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script>
"use strict";
let register_report_table = $('#register_report_table').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
        url: '{{ action([Modules\Report\Http\Controllers\SalesReportController::class, 'getRegisterReport']) }}',
        data: function(d) {
            d.register_user_id = $('#register_user_id').val();
            d.register_status = $('#register_status').val();
        }
    },
    columns: [
        { data: 'created_at', name: 'created_at' },
        { data: 'closed_at', name: 'closed_at', defaultContent: '' },
        { data: 'location_name', name: 'location_name' },
        { data: 'user_name', name: 'user_name' },
        { data: 'total_card_payment', name: 'total_card_payment', searchable: false },
        { data: 'total_cheque_payment', name: 'total_cheque_payment', searchable: false },
        { data: 'total_cash_payment', name: 'total_cash_payment', searchable: false },
        { data: 'total_bank_transfer_payment', name: 'total_bank_transfer_payment', searchable: false },
        { data: 'total', name: 'total', orderable: false, searchable: false },
        { data: 'action', name: 'action', orderable: false, searchable: false },
    ],
    "footerCallback": function(row, data) {
        let total_card = 0, total_cheque = 0, total_cash = 0, total_bank = 0, total = 0;
        for (let r of data) {
            total_card += parseFloat($(r.total_card_payment).data('orig-value') || 0);
            total_cheque += parseFloat($(r.total_cheque_payment).data('orig-value') || 0);
            total_cash += parseFloat($(r.total_cash_payment).data('orig-value') || 0);
            total_bank += parseFloat($(r.total_bank_transfer_payment).data('orig-value') || 0);
            total += parseFloat($(r.total).data('orig-value') || 0);
        }
        $('.footer_total_card_payment').html((total_card));
        $('.footer_total_cheque_payment').html((total_cheque));
        $('.footer_total_cash_payment').html((total_cash));
        $('.footer_total_bank_transfer_payment').html((total_bank));
        $('.footer_total').html((total));
    },
});

$('#register_user_id, #register_status').change(function() {
    register_report_table.ajax.reload();
});
</script>
@endsection
