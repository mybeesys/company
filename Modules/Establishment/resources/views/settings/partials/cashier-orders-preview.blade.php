@php
    $t = $t ?? fn (string $key) => __('establishment::cashier-orders.'.$key);
@endphp
<div class="cashier-order-preview-sticky">
    <x-form.form-card :title="$t('live_preview')" bodyClass="d-flex flex-column gap-5">
        <p class="text-muted fs-7 mb-0">{{ $t('preview_example') }}</p>
        <div class="cashier-order-preview-display" data-preview-size>
            <div class="text-muted fs-8 text-uppercase fw-semibold mb-2">{{ $t('preview_display') }}</div>
            <div class="cashier-order-preview-number" data-preview="display">{{ $preview['display'] }}</div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="cashier-order-preview-chip">
                    <span class="text-muted fs-8">{{ $t('preview_system') }}</span>
                    <strong class="fs-6" data-preview="system">{{ $preview['system'] }}</strong>
                </div>
            </div>
            <div class="col-12">
                <div class="cashier-order-preview-chip">
                    <span class="text-muted fs-8">{{ $t('preview_kitchen') }}</span>
                    <strong class="fs-6" data-preview="kitchen">{{ $preview['kitchen'] }}</strong>
                </div>
            </div>
        </div>
    </x-form.form-card>
</div>
