@php
    $localeAr = app()->getLocale() === 'ar';
    $companyName = (string) config('app.name');
    $companyNameAr = $companyName;
    $companyLogoUrl = '';
    $companyTaxNumber = '';

    $row = \App\Support\CompanyDocumentLogo::companyRow();
    if ($row) {
        $companyName = (string) ($row->name ?? $companyName);
        $companyNameAr = (string) ($row->name_ar ?? $row->name ?? $companyName);
        $companyTaxNumber = trim((string) ($row->tax_number ?? ''));
        $companyLogoUrl = (string) (\App\Support\CompanyDocumentLogo::src($row) ?? '');
    }

    $opDate = isset($journal) && $journal->operation_date
        ? \Illuminate\Support\Carbon::parse($journal->operation_date)->format('Y-m-d')
        : '—';
@endphp

<header class="mj-print-header">
    <div class="mj-print-header-row">
        <div class="mj-print-brand">
            @if ($companyLogoUrl)
                <img src="{{ $companyLogoUrl }}" alt="" class="mj-print-logo">
            @endif
            <div>
                <h1 class="mj-print-title">{{ __('accounting::lang.print_journalEntry') }}</h1>
                <div class="mj-print-company">{{ $localeAr ? $companyNameAr : $companyName }}</div>
                @if ($companyTaxNumber)
                    <div class="mj-print-meta">{{ $companyTaxNumber }}</div>
                @endif
            </div>
        </div>
        <div class="mj-print-side">
            <div>{{ __('accounting::lang.printed_at') }}: {{ now()->format('Y-m-d H:i') }}</div>
            <div>{{ __('accounting::lang.journalEntry_date') }}: {{ $opDate }}</div>
            <div class="mj-print-currency">{{ __('accounting::lang.currency_sar') }}</div>
        </div>
    </div>
</header>
