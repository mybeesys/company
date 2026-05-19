@php
    use App\Helpers\CurrencyHelper;
    $amountValue = (float) ($amount ?? 0);
    $isNegative = CurrencyHelper::is_negative_amount($amountValue);
    $compareValue = isset($compareAmount) ? (float) $compareAmount : null;
@endphp
<td class="is-fin-amount text-end {{ $isNegative ? 'is-negative' : '' }}">
    @format_accounting_amount($amountValue)
    @if($compareValue !== null)
        <div class="d-block text-muted" style="font-size:0.72rem;font-weight:400;">
            @format_accounting_amount($compareValue)
        </div>
    @endif
</td>
