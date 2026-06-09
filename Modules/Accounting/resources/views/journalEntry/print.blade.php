<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('accounting::lang.print_journalEntry') }}: {{ $journal->ref_no ?? '' }}</title>
    @include('accounting::journalEntry.partials._print_styles')
    <script>
        window.onload = function() {
            window.print();
        };
        window.onafterprint = function() {
            window.location.href = @json(route('journal-entry-index'));
        };
    </script>
</head>
<body class="mj-print-body">
@php
    $localeAr = app()->getLocale() === 'ar';
    $journal->loadMissing([
        'added_by',
        'transactions.account',
        'transactions.costCenter',
    ]);

    $totalDebit = 0.0;
    $totalCredit = 0.0;
    foreach ($journal->transactions as $tx) {
        if ($tx->type === 'debit') {
            $totalDebit += (float) $tx->amount;
        } elseif ($tx->type === 'credit') {
            $totalCredit += (float) $tx->amount;
        }
    }
    $diff = round($totalDebit - $totalCredit, 2);
    $isBalanced = abs($diff) < 0.005;
    $opDate = $journal->operation_date
        ? \Illuminate\Support\Carbon::parse($journal->operation_date)->format('Y-m-d')
        : '—';
@endphp

@include('accounting::journalEntry.partials._print_header', ['journal' => $journal])

<article class="jr-entry">
    <header class="jr-entry-header">
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3" style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;">
            @if ($isBalanced)
                <span class="jr-badge-balanced">✓ {{ __('accounting::lang.balanced') }}</span>
            @else
                <span class="jr-badge-unbalanced">! {{ __('accounting::lang.unbalanced') }}</span>
            @endif
            <span class="jr-badge-manual">{{ __('accounting::lang.journal_source_manual_journal') }}</span>
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
                <label>{{ __('accounting::lang.journalEntry_date') }}</label>
                <span class="value">{{ $opDate }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.created_by') }}</label>
                <span class="value">{{ $journal->added_by?->name ?? '—' }}</span>
            </div>
            <div class="jr-meta-item">
                <label>{{ __('accounting::lang.journal_status') }}</label>
                <span class="value">{{ $isBalanced ? __('accounting::lang.balanced') : __('accounting::lang.unbalanced') }}</span>
            </div>
            @if (! empty($journal->note))
                <div class="jr-meta-item" style="grid-column: 1 / -1;">
                    <label>{{ __('accounting::lang.additionalNotes') }}</label>
                    <span class="value" style="font-weight:400;">{{ $journal->note }}</span>
                </div>
            @endif
        </div>
    </header>

    <table class="jr-lines-table">
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
                    $account = $transaction->account;
                    $accountName = $account
                        ? ($localeAr ? ($account->name_ar ?: $account->name_en) : ($account->name_en ?: $account->name_ar))
                        : '—';
                    $cc = $transaction->costCenter;
                    $ccLabel = $cc
                        ? ($localeAr ? ($cc->name_ar ?: $cc->name_en) : ($cc->name_en ?: $cc->name_ar))
                        : '—';
                    $amt = number_format((float) $transaction->amount, 2);
                @endphp
                <tr>
                    <td class="col-gl">{{ $account->gl_code ?? '—' }}</td>
                    <td>{{ $accountName }}</td>
                    <td>{{ $ccLabel }}</td>
                    <td>{{ $transaction->note ?: '—' }}</td>
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

    <footer class="jr-entry-footer">
        <div class="jr-footer-totals">
            <div class="jr-footer-amounts">
                <span>
                    {{ __('accounting::lang.debit') }}:
                    <strong style="color:var(--jr-debit);">{{ number_format($totalDebit, 2) }}</strong>
                </span>
                <span>
                    {{ __('accounting::lang.credit') }}:
                    <strong style="color:var(--jr-credit);">{{ number_format($totalCredit, 2) }}</strong>
                </span>
                @if (! $isBalanced)
                    <span style="color:#8a7344;">
                        {{ __('accounting::lang.difference') }}:
                        <strong>{{ number_format(abs($diff), 2) }}</strong>
                    </span>
                @endif
            </div>
            @if ($isBalanced)
                <span class="jr-badge-balanced">✓ {{ __('accounting::lang.balanced') }}</span>
            @endif
        </div>
    </footer>
</article>

<div class="mj-print-actions no-print">
    <button type="button" class="btn-primary" onclick="window.print()">{{ __('general.print') }}</button>
    <a href="{{ route('journal-entry-show', $journal->id) }}">{{ __('accounting::lang.view_journalEntry') }}</a>
    <a href="{{ route('journal-entry-index') }}">{{ __('accounting::lang.journalEntry') }}</a>
</div>

</body>
</html>
