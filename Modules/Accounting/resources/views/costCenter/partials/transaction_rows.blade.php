@foreach ($transactions as $transaction)
    @php
        $detailUrl = $transaction->ledgerDetailUrl();
        $refNo = $transaction->displayRefNo();
        $subType = $transaction->sub_type ?? null;
        $typeLabel = $subType
            ? (\Illuminate\Support\Facades\Lang::has('accounting::lang.' . $subType)
                ? __('accounting::lang.' . $subType)
                : $subType)
            : '—';
        $accountLabel = $transaction->account
            ? $transaction->account->gl_code . ' - ' . (app()->getLocale() == 'ar' ? $transaction->account->name_ar : $transaction->account->name_en)
            : '—';
        $narration = \Modules\Accounting\Support\AccountingNote::resolveForDisplay(
            $transaction->note,
            $transaction->accTransMapping?->note,
            true
        );
    @endphp
    <tr>
        <td>
            @if ($detailUrl)
                <a class="text-gray-900 fw-bold text-hover-primary mb-1 fs-6" href="{{ $detailUrl }}"
                    title="{{ __('employee::fields.show') }}">
                    {{ $refNo }}
                </a>
            @else
                <span class="text-gray-900 fw-bold mb-1 fs-6">{{ $refNo }}</span>
            @endif
            <span class="badge badge-light-primary fs-8 d-inline-block mt-1">{{ $typeLabel }}</span>
        </td>
        <td>
            <span class="text-gray-900 fw-semibold fs-7">
                {{ \Carbon\Carbon::parse($transaction->operation_date)->format('d/m/Y') }}
            </span>
        </td>
        <td>
            <span class="text-gray-800 fs-7">{{ $accountLabel }}</span>
        </td>
        <td>
            <span class="text-gray-800 fs-7">{{ $narration }}</span>
        </td>
        <td>
            <span class="text-gray-900 fw-bold mb-1 fs-6">{{ $transaction->createdBy?->name ?? '—' }}</span>
        </td>
        <td class="text-end">
            @if ($transaction->type == 'debit')
                <span class="fw-bold fs-6">{{ number_format($transaction->amount, 2) }}</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td class="text-end">
            @if ($transaction->type == 'credit')
                <span class="fw-bold fs-6">{{ number_format($transaction->amount, 2) }}</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
    </tr>
@endforeach
