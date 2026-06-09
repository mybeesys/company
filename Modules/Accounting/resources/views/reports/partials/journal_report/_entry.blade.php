{{-- Single journal entry block — screen, browser print, PDF HTML --}}
@php
    $localeAr = app()->getLocale() === 'ar';
    $isBalanced = (float) ($journal->journal_diff ?? 0) < 0.005;
    $opDate = $journal->operation_date
        ? \Illuminate\Support\Carbon::parse($journal->operation_date)->format('Y-m-d')
        : '—';
    $inferSource = $jrInferSource ?? null;
    $sourceLabel = is_callable($inferSource) ? $inferSource($journal) : __('accounting::lang.automatic_journal');
    $costCenterMap = $jrCostCenterMap ?? collect();
@endphp

<article class="jr-entry journal-card" id="journal-card-{{ $journal->id }}">
    <header class="jr-entry-header">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if ($isBalanced)
                    <span class="jr-badge-balanced">
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        {{ __('accounting::lang.balanced') }}
                    </span>
                @else
                    <span class="jr-badge-unbalanced">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                        {{ __('accounting::lang.unbalanced') }}
                    </span>
                @endif
            </div>
            <div class="jr-entry-actions no-print">
                <button type="button" class="btn btn-sm btn-light-primary"
                    onclick="printJournalCard('journal-card-{{ $journal->id }}')"
                    title="{{ __('general.print') }}">
                    <i class="fas fa-print me-1"></i>{{ __('general.print') }}
                </button>
            </div>
        </div>

        <div class="jr-entry-header-grid">
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.journal_entry_no') }}</label>
                <span class="value value--mono">#{{ $journal->id }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.ref_no') }}</label>
                <span class="value value--mono">{{ $journal->ref_no }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.operation_date') }}</label>
                <span class="value">{{ $opDate }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.operation_type') }}</label>
                <span class="value">{{ $sourceLabel }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.created_by') }}</label>
                <span class="value">{{ $journal->added_by?->name ?? '—' }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.journal_status') }}</label>
                <span class="value">
                    {{ $isBalanced ? __('accounting::lang.balanced') : __('accounting::lang.unbalanced') }}
                </span>
            </div>
            @if (! empty($journal->note))
                <div class="jr-meta-item" style="grid-column: 1 / -1;">
                    <label>{{ __('accounting::lang.note') }}</label>
                    <span class="value fw-normal">{{ $journal->note }}</span>
                </div>
            @endif
        </div>
    </header>

    <div class="table-responsive">
        <table class="jr-lines-table table mb-0">
            <thead>
                <tr>
                    <th class="col-gl">{{ __('accounting::lang.gl_code') }}</th>
                    <th>{{ __('accounting::lang.account_name') }}</th>
                    <th>{{ __('accounting::lang.cost_center') }}</th>
                    <th>{{ __('accounting::lang.line_description') }}</th>
                    <th class="col-amount">{{ __('accounting::lang.debit') }}</th>
                    <th class="col-amount">{{ __('accounting::lang.credit') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($journal->transactions as $transaction)
                    @php
                        $accountName = $localeAr
                            ? ($transaction->name_ar ?: $transaction->name_en)
                            : ($transaction->name_en ?: $transaction->name_ar);
                        $cc = $transaction->cost_center_id
                            ? ($costCenterMap[$transaction->cost_center_id] ?? null)
                            : null;
                        $ccLabel = $cc
                            ? ($localeAr ? ($cc->name_ar ?: $cc->name_en) : ($cc->name_en ?: $cc->name_ar))
                            : '—';
                        $amt = number_format((float) $transaction->amount, 2);
                    @endphp
                    <tr>
                        <td class="col-gl">{{ $transaction->gl_code }}</td>
                        <td>{{ $accountName }}</td>
                        <td class="text-muted small">{{ $ccLabel }}</td>
                        <td class="small">{{ $transaction->note ?: '—' }}</td>
                        <td class="col-amount {{ $transaction->type === 'debit' ? 'amount-debit' : 'amount-empty' }}">
                            {{ $transaction->type === 'debit' ? $amt : '—' }}
                        </td>
                        <td class="col-amount {{ $transaction->type === 'credit' ? 'amount-credit' : 'amount-empty' }}">
                            {{ $transaction->type === 'credit' ? $amt : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <footer class="jr-entry-footer">
        <div class="jr-footer-totals">
            <div class="jr-footer-amounts">
                <span>
                    {{ __('accounting::lang.debit') }}:
                    <strong class="text-primary">{{ number_format((float) ($journal->journal_debit ?? 0), 2) }}</strong>
                </span>
                <span>
                    {{ __('accounting::lang.credit') }}:
                    <strong class="text-success">{{ number_format((float) ($journal->journal_credit ?? 0), 2) }}</strong>
                </span>
                @if (! $isBalanced)
                    <span class="text-warning">
                        {{ __('accounting::lang.difference') }}:
                        <strong>{{ number_format((float) ($journal->journal_diff ?? 0), 2) }}</strong>
                    </span>
                @endif
            </div>
            @if ($isBalanced)
                <span class="jr-badge-balanced mb-0">
                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                    {{ __('accounting::lang.balanced') }}
                </span>
            @endif
        </div>
    </footer>
</article>
