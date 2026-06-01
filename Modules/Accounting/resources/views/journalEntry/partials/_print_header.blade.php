@php
    $localeAr = app()->getLocale() === 'ar';
    $companyName = (string) config('app.name');
    $companyNameAr = $companyName;
    $companyLogoUrl = '';
    $companyTaxNumber = '';

    if (function_exists('get_company_id') && get_company_id()) {
        $row = \Illuminate\Support\Facades\DB::connection('mysql')
            ->table('companies')
            ->find(get_company_id());
        if ($row) {
            $companyName = (string) ($row->name ?? $companyName);
            $companyNameAr = (string) ($row->name_ar ?? $row->name ?? $companyName);
            $companyTaxNumber = trim((string) ($row->tax_number ?? ''));
            foreach (['logo', 'logo_path', 'image', 'company_logo'] as $col) {
                if (! empty($row->{$col})) {
                    $path = (string) $row->{$col};
                    $companyLogoUrl = function_exists('central_public_storage_url_for_path')
                        ? central_public_storage_url_for_path($path)
                        : $path;
                    break;
                }
            }
        }
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
