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

    $periodFrom = isset($startDate) && $startDate
        ? \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d')
        : '—';
    $periodTo = isset($endDate) && $endDate
        ? \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d')
        : '—';
@endphp

<header class="jr-print-doc-header">
    <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-3">
            @if ($companyLogoUrl)
                <img src="{{ $companyLogoUrl }}" alt="" style="max-height: 52px; max-width: 120px; object-fit: contain;">
            @endif
            <div>
                <h2 class="doc-title mb-0">{{ __('accounting::lang.journal_report') }}</h2>
                <div class="jr-print-doc-meta">
                    {{ $localeAr ? $companyNameAr : $companyName }}
                    @if ($companyTaxNumber)
                        · {{ $companyTaxNumber }}
                    @endif
                </div>
            </div>
        </div>
        <div class="text-end small text-muted">
            <div>{{ __('accounting::lang.printed_at') }}: {{ now()->format('Y-m-d H:i') }}</div>
            <div>{{ __('accounting::lang.from_date') }}: {{ $periodFrom }}</div>
            <div>{{ __('accounting::lang.to_date') }}: {{ $periodTo }}</div>
            <div class="fw-semibold text-dark mt-1">{{ __('accounting::lang.currency_sar') }}</div>
        </div>
    </div>
</header>
