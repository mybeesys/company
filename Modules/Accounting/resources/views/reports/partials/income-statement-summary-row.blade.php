@php
    $rowClass = $rowClass ?? 'is-subtotal';
    $colspan = 1;
@endphp
<tr class="{{ $rowClass }}">
    <td>{{ $label }}</td>
    @include('accounting::reports.partials.income-statement-amount', ['amount' => $amount])
</tr>
