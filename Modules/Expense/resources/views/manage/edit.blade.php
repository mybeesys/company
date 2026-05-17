@extends('layouts.app')

@section('title', __('expense::lang.edit_heading'))

@section('content')
    <div class="container-fluid py-4">
        <div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('expenses.manage') }}" class="btn btn-light btn-sm">← @lang('expense::lang.nav_manage')</a>
            <a href="{{ route('expenses.manage.show', $expense->id) }}" class="btn btn-light btn-sm">@lang('employee::fields.show')</a>
        </div>

        <div class="alert alert-light-primary">@lang('expense::lang.immutable_notice')</div>

        <div class="card mb-4">
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <div class="text-muted fs-8">@lang('expense::lang.field_debit_account')</div>
                    @if ($expense->debitAccount)
                        <div class="fw-semibold">{{ app()->getLocale() === 'ar' ? $expense->debitAccount->name_ar : $expense->debitAccount->name_en }}</div>
                        <div class="text-muted">{{ $expense->debitAccount->gl_code }}</div>
                    @else
                        <div>—</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8">@lang('expense::lang.field_credit_account')</div>
                    <div class="fw-semibold">{{ app()->getLocale() === 'ar' ? $expense->creditAccount->name_ar : $expense->creditAccount->name_en }}</div>
                    <div class="text-muted">{{ $expense->creditAccount->gl_code }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8">@lang('expense::lang.field_amount') (@lang('expense::lang.table_gross'))</div>
                    <div class="fw-bold fs-4">{{ number_format($expense->gross_amount, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted fs-8">@lang('expense::lang.table_net') / @lang('expense::lang.table_tax')</div>
                    <div>{{ number_format($expense->net_amount, 2) }} / {{ number_format((float) $expense->getRawOriginal('tax'), 2) }}</div>
                    @if ($totalTaxPercent !== '')
                        <div class="text-muted fs-8 mt-1">{{ __('expense::lang.percent_vat') }}: {{ $totalTaxPercent }}%</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">@lang('expense::lang.edit_heading')</h2>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('expenses.manage.update', $expense->id) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label required">@lang('expense::lang.field_cost_center')</label>
                            <select name="cost_center_id" id="expense_edit_cost_center" class="form-select form-select-solid" required>
                                @foreach ($costCenters as $cc)
                                    <option value="{{ $cc->id }}" @selected((int) $cc->id === (int) $expense->cost_center_id)>
                                        {{ $cc->account_center_number ?? '' }}
                                        {{ app()->getLocale() === 'ar' ? $cc->name_ar : $cc->name_en }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">@lang('expense::lang.field_date')</label>
                            <input type="date" name="date" class="form-control" required value="{{ $expense->date->format('Y-m-d') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">@lang('expense::lang.field_description')</label>
                            <textarea name="description" rows="8" class="form-control" required>{{ $expense->description }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">@lang('expense::lang.field_attachments')</label>
                            <input type="file" name="attachments[]" class="form-control" multiple>
                            @if ($expense->attachments->isNotEmpty())
                                <ul class="mt-3 mb-0">
                                    @foreach ($expense->attachments as $att)
                                        <li class="d-flex justify-content-between align-items-center gap-2 py-1">
                                            <a href="{{ Storage::disk('public')->url($att->path) }}" target="_blank">{{ $att->original_name ?? basename($att->path) }}</a>
                                            <form method="post" action="{{ route('expenses.manage.attachments.destroy', [$expense->id, $att->id]) }}"
                                                onsubmit="return confirm(@json(__('messages.are_you_sure')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-light-danger">@lang('employee::fields.delete')</button>
                                            </form>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script>
        $(function() {
            $('#expense_edit_cost_center').select2({
                width: '100%',
                dir: document.documentElement.getAttribute('dir') === 'rtl' ? 'rtl' : 'ltr',
            });
        });
    </script>
@endsection
