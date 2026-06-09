@extends('layouts.app')
@section('title', __('report::fields.register_report'))

@section('css')
<style>
    .rr-wrap {
        --rr-radius: 16px;
        --rr-border: #eef1f6;
        --rr-brand: var(--bs-primary);
        --rr-brand-light: var(--bs-primary-light);
        --rr-brand-subtle: var(--bs-primary-bg-subtle, #f8efcf);
        --rr-brand-border: var(--bs-primary-border-subtle, #eed592);
    }

    .rr-hero {
        background: linear-gradient(135deg, #ffffff 0%, var(--rr-brand-light) 55%, var(--rr-brand-subtle) 100%);
        border: 1px solid var(--rr-brand-border);
        border-radius: var(--rr-radius);
        padding: 1.5rem 1.75rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
    }

    .rr-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: #fff;
        color: var(--rr-brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        box-shadow: 0 8px 18px rgba(var(--bs-primary-rgb), .18);
    }

    .rr-filter-card {
        background: #fff;
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .04);
    }

    .rr-filter-label {
        font-size: .8rem;
        font-weight: 600;
        color: #7e8299;
        margin-bottom: .45rem;
    }

    .rr-table-card {
        border: 1px solid var(--rr-border);
        border-radius: var(--rr-radius);
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        background: #fff;
        padding: 1.15rem 1.35rem 1.35rem;
    }

    .rr-wrap .dataTables_wrapper .dataTables_length,
    .rr-wrap .dataTables_wrapper .dataTables_filter {
        padding: 0 .15rem;
        margin-bottom: .85rem;
    }

    .rr-wrap .dataTables_wrapper .dataTables_info,
    .rr-wrap .dataTables_wrapper .dataTables_paginate {
        padding: .85rem .15rem 0;
    }

    #register_report_table {
        font-variant-numeric: tabular-nums;
        margin-bottom: 0 !important;
    }

    #register_report_table thead th {
        background: var(--rr-brand-light) !important;
        color: #3f4254 !important;
        font-weight: 800;
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .02em;
        border-bottom: 2px solid var(--rr-brand-border) !important;
        white-space: nowrap;
        vertical-align: middle;
        padding: 0.8rem 1.15rem !important;
    }

    #register_report_table tbody td {
        vertical-align: middle;
        padding: 0.75rem 1.15rem !important;
        border-color: #eff2f5;
    }

    #register_report_table tfoot td {
        background: var(--rr-brand-subtle);
        font-weight: 700;
        border-top: 2px solid var(--rr-brand-border);
        padding: 0.85rem 1.15rem !important;
        vertical-align: middle;
    }

    #register_report_table thead th:first-child,
    #register_report_table tbody td:first-child,
    #register_report_table tfoot td:first-child {
        padding-inline-start: 1.35rem !important;
    }

    #register_report_table thead th:last-child,
    #register_report_table tbody td:last-child,
    #register_report_table tfoot td:last-child {
        padding-inline-end: 1.35rem !important;
    }

    .rr-amount {
        font-variant-numeric: tabular-nums;
    }
</style>
@stop

@section('content')
<div class="rr-wrap">
    <div class="rr-hero">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="rr-hero-icon">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <h2 class="fs-3 fw-bold text-gray-900 mb-1">@lang('report::fields.register_report')</h2>
                <p class="text-muted fs-7 mb-0">@lang('report::fields.register_report_details')</p>
            </div>
        </div>
    </div>

    <div class="rr-filter-card">
        <form id="register_report_filter_form" method="GET"
            action="{{ action([Modules\Report\Http\Controllers\SalesReportController::class, 'getRegisterReport']) }}">
            <div class="row g-4 align-items-end">
                <div class="col-md-4">
                    <div class="rr-filter-label">@lang('report::fields.user')</div>
                    <select name="register_user_id" id="register_user_id" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('report::fields.all_users')</option>
                        @foreach ($users as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="rr-filter-label">@lang('report::fields.status')</div>
                    <select name="register_status" id="register_status" class="form-select form-select-solid" data-control="select2">
                        <option value="">@lang('accounting::lang.all')</option>
                        <option value="open">@lang('report::fields.open')</option>
                        <option value="close">@lang('report::fields.close')</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="rr-table-card">
        <div class="table-responsive">
            <table class="table align-middle table-row-bordered table-hover fs-6 w-100" id="register_report_table">
                <thead>
                    <tr>
                        <th>@lang('report::fields.open_time')</th>
                        <th>@lang('report::fields.close_time')</th>
                        <th>@lang('report::fields.location')</th>
                        <th>@lang('report::fields.user')</th>
                        <th>@lang('report::fields.status')</th>
                        <th class="text-end">@lang('report::fields.total_card_slips')</th>
                        <th class="text-end">@lang('report::fields.total_cheques')</th>
                        <th class="text-end">@lang('report::fields.total_cash')</th>
                        <th class="text-end">@lang('report::fields.total_bank_transfer')</th>
                        <th class="text-end">@lang('report::fields.total')</th>
                        <th class="text-center">@lang('messages.actions')</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-end">@lang('report::fields.total'):</td>
                        <td class="text-end footer_total_card_payment"></td>
                        <td class="text-end footer_total_cheque_payment"></td>
                        <td class="text-end footer_total_cash_payment"></td>
                        <td class="text-end footer_total_bank_transfer_payment"></td>
                        <td class="text-end footer_total"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script>
"use strict";

let register_report_table;

function rrParseAmount(cellHtml) {
    if (!cellHtml) {
        return 0;
    }
    const match = String(cellHtml).match(/data-orig-value="([^"]+)"/);
    return match ? parseFloat(match[1]) : 0;
}

$(document).ready(function() {
    register_report_table = $('#register_report_table').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: {
            url: '{{ action([Modules\Report\Http\Controllers\SalesReportController::class, 'getRegisterReport']) }}',
            data: function(d) {
                d.register_user_id = $('#register_user_id').val();
                d.register_status = $('#register_status').val();
            }
        },
        columns: [
            { data: 'created_at', name: 'cash_registers.created_at' },
            { data: 'closed_at', name: 'cash_registers.closed_at', orderable: true, searchable: false },
            { data: 'location_name', name: 'location_name', orderable: false, searchable: false },
            { data: 'user_name', name: 'user_name', orderable: false, searchable: false },
            { data: 'status', name: 'cash_registers.status', orderable: false, searchable: false },
            { data: 'total_card_payment', name: 'total_card_payment', orderable: false, searchable: false, className: 'text-end' },
            { data: 'total_cheque_payment', name: 'total_cheque_payment', orderable: false, searchable: false, className: 'text-end' },
            { data: 'total_cash_payment', name: 'total_cash_payment', orderable: false, searchable: false, className: 'text-end' },
            { data: 'total_bank_transfer_payment', name: 'total_bank_transfer_payment', orderable: false, searchable: false, className: 'text-end' },
            { data: 'total', name: 'total', orderable: false, searchable: false, className: 'text-end' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        footerCallback: function(row, data) {
            let total_card = 0, total_cheque = 0, total_cash = 0, total_bank = 0, total = 0;
            for (let i = 0; i < data.length; i++) {
                const r = data[i];
                total_card += rrParseAmount(r.total_card_payment);
                total_cheque += rrParseAmount(r.total_cheque_payment);
                total_cash += rrParseAmount(r.total_cash_payment);
                total_bank += rrParseAmount(r.total_bank_transfer_payment);
                total += rrParseAmount(r.total);
            }
            $('.footer_total_card_payment').html(total_card.toFixed(2));
            $('.footer_total_cheque_payment').html(total_cheque.toFixed(2));
            $('.footer_total_cash_payment').html(total_cash.toFixed(2));
            $('.footer_total_bank_transfer_payment').html(total_bank.toFixed(2));
            $('.footer_total').html(total.toFixed(2));
        },
    });

    $('#register_user_id, #register_status').on('change', function() {
        register_report_table.ajax.reload();
    });

    $('#register_user_id, #register_status').select2();
});
</script>
@endsection
