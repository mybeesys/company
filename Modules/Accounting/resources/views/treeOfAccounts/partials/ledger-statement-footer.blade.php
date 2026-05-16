@php
    $localeAr = $locale_ar ?? (app()->getLocale() === 'ar');
@endphp
<table width="100%" style="font-size: 7.5px; color: #444; font-family: DejaVu Sans, sans-serif; border-top: 1px solid #ccc; padding-top: 4px;">
    <tr>
        <td width="50%" style="text-align: {{ $localeAr ? 'right' : 'left' }};">
            @if (! empty($company?->phone))
                <div>@lang('accounting::lang.ledger_stmt_telephone'): {{ $company->phone }}</div>
            @endif
            @if (! empty($company?->fax))
                <div>@lang('accounting::lang.ledger_stmt_fax'): {{ $company->fax }}</div>
            @endif
            @if (! empty($company?->tax_number))
                <div>@lang('accounting::lang.ledger_stmt_tax_reg'): {{ $company->tax_number }}</div>
            @endif
        </td>
        <td width="50%" style="text-align: {{ $localeAr ? 'left' : 'right' }};">
            <div>
                @lang('accounting::lang.ledger_stmt_page') {PAGENO} / {nbpg}
            </div>
            <div>@lang('accounting::lang.ledger_stmt_printed_at'): {{ $printed_at ?? '' }}</div>
            <div style="font-weight:700;">@lang('accounting::lang.ledger_stmt_original')</div>
        </td>
    </tr>
</table>
