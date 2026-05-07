<div class="voucher-view">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <div class="fw-bold fs-4 mb-1">{{ $pageTitle }}</div>
            <div class="text-muted">
                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                <span class="mx-2">·</span>
                {{ __('accounting::lang.amount') }}: <span class="fw-bold">{{ number_format((float) $amount, 2) }}</span>
            </div>
            @if (!empty($note))
                <div class="mt-2 text-gray-800">{{ $note }}</div>
            @endif
        </div>

        <div class="d-flex gap-2">
            @if (!empty($pdfUrl))
                <a href="{{ $pdfUrl }}" class="btn btn-light-danger" target="_blank">@lang('accounting::lang.download_pdf')</a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="border rounded-3 p-4 h-100" style="border-color:#F3C6C6;">
                <div class="fw-bold mb-2">{{ __('accounting::lang.account-debit') }}</div>
                <div class="text-muted mb-2">{{ $debitHint ?? '' }}</div>
                <div class="fw-semibold">{{ $debitAccountLabel }}</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-3 p-4 h-100" style="border-color:#F3C6C6;">
                <div class="fw-bold mb-2">{{ __('accounting::lang.account-credit') }}</div>
                <div class="text-muted mb-2">{{ $creditHint ?? '' }}</div>
                <div class="fw-semibold">{{ $creditAccountLabel }}</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-3 p-4 h-100" style="border-color:#F3C6C6;">
                <div class="fw-bold mb-2">{{ __('accounting::lang.cost_center') }}</div>
                <div class="fw-semibold">{{ $costCenterLabel }}</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="border rounded-3 p-4 h-100" style="border-color:#F3C6C6;">
                <div class="fw-bold mb-2">{{ __('accounting::lang.added_by') }}</div>
                <div class="fw-semibold">{{ $createdByLabel }}</div>
            </div>
        </div>
    </div>
</div>

