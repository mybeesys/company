@extends('layouts.app')

@section('title', __('expense::lang.show_heading'))

@section('content')
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-6">
            <a href="{{ route('expenses.manage') }}" class="btn btn-light btn-sm">← @lang('expense::lang.nav_manage')</a>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('expenses.manage.edit', $expense->id) }}" class="btn btn-sm btn-light-primary">@lang('messages.edit')</a>
                <a href="{{ route('expenses.manage.create', ['duplicate_from' => $expense->id]) }}" class="btn btn-sm btn-light-info">@lang('messages.duplicate')</a>
                @if ($expense->acc_trans_mapping_id)
                    <a href="{{ route('journal-entry-show', $expense->acc_trans_mapping_id) }}" class="btn btn-sm btn-light" target="_blank" rel="noopener">@lang('accounting::lang.view_journalEntry')</a>
                @endif
                <form method="post" action="{{ route('expenses.manage.destroy', $expense->id) }}" class="d-inline"
                    onsubmit="return confirm(@json(__('messages.are_you_sure')));">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-light-danger">@lang('employee::fields.delete')</button>
                </form>
            </div>
        </div>

        <div class="card mb-4 shadow-sm border-0">
            <div class="card-header border-0 pt-6 pb-0">
                <h1 class="fs-3 fw-bold mb-1">@lang('expense::lang.show_heading')</h1>
                <div class="text-muted fs-7">#{{ $expense->id }} · {{ $expense->date?->format('Y-m-d') }}</div>
            </div>
            <div class="card-body row g-6 pt-6">
                <div class="col-md-6 col-lg-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.field_debit_account')</div>
                    @if ($expense->debitAccount)
                        <div class="fw-semibold fs-6">{{ app()->getLocale() === 'ar' ? $expense->debitAccount->name_ar : $expense->debitAccount->name_en }}</div>
                        <div class="text-muted fs-8">{{ $expense->debitAccount->gl_code }}</div>
                    @else
                        <div>—</div>
                    @endif
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.field_cost_center')</div>
                    @if ($expense->costCenter)
                        <div class="fw-semibold fs-6">{{ app()->getLocale() === 'ar' ? $expense->costCenter->name_ar : $expense->costCenter->name_en }}</div>
                    @else
                        <div>—</div>
                    @endif
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.field_credit_account')</div>
                    @if ($expense->creditAccount)
                        <div class="fw-semibold">{{ app()->getLocale() === 'ar' ? $expense->creditAccount->name_ar : $expense->creditAccount->name_en }}</div>
                        <div class="text-muted fs-8">{{ $expense->creditAccount->gl_code }}</div>
                    @else
                        <div>—</div>
                    @endif
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.field_tax')</div>
                    @if ($expense->tax_id && $expense->appliedTax)
                        <div>{{ app()->getLocale() === 'ar' ? $expense->appliedTax->name : ($expense->appliedTax->name_en ?: $expense->appliedTax->name) }}</div>
                        @if ($totalTaxPercent !== '')
                            <div class="text-muted fs-8">{{ $totalTaxPercent }}%</div>
                        @endif
                    @else
                        <div>—</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.table_net')</div>
                    <div class="fw-bold fs-3 text-primary">{{ number_format($expense->net_amount, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.table_tax')</div>
                    <div class="fw-bold fs-3">{{ number_format((float) $expense->getRawOriginal('tax'), 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8 fw-semibold text-uppercase">@lang('expense::lang.table_gross')</div>
                    <div class="fw-bold fs-3">{{ number_format($expense->gross_amount, 2) }}</div>
                </div>
                <div class="col-12">
                    <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">@lang('expense::lang.field_description')</div>
                    <div class="border rounded border-gray-300 bg-light-subtle p-4 fs-6 whitespace-pre-wrap">{{ $expense->description }}</div>
                </div>
                @if ($expense->attachments->isNotEmpty())
                    <div class="col-12">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-2">@lang('expense::lang.field_attachments')</div>
                        <ul class="mb-0 ps-4">
                            @foreach ($expense->attachments as $att)
                                <li class="py-1">
                                    <a href="{{ Storage::disk('public')->url($att->path) }}" target="_blank" rel="noopener">{{ $att->original_name ?? basename($att->path) }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
