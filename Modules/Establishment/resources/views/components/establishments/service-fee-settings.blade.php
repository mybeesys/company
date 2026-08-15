@props([
    'serviceFeeRows' => [],
    'diningTypes' => null,
    'cashierPaymentRows' => [],
])
@php
    $locale = app()->getLocale();
    $rows = old('service_fee_rows', $serviceFeeRows ?? []);
    if (! is_array($rows) || $rows === []) {
        $rows = [[
            'id' => null,
            'name_ar' => '',
            'name_en' => '',
            'service_fee_type' => '0',
            'amount' => null,
            'application_type' => '1',
            'calculation_method' => '0',
            'taxable' => false,
            'active' => true,
            'auto_apply_type' => '',
            'dining_type_ids' => [],
            'guestCount' => null,
            'credit_type' => null,
            'from_date' => null,
            'to_date' => null,
        ]];
    }
@endphp
<div class="establishment-service-fees d-flex flex-column flex-row-fluid gap-7 gap-lg-10" id="service_fees_root">
    <x-form.form-card bodyClass="d-flex flex-column gap-5" :title="__('establishment::general.service_fee_settings')">
        <p class="text-muted mb-0">@lang('establishment::general.service_fee_settings_hint')</p>
        <x-form.field-hint :hint="__('establishment::general.service_fee_row_hint')" />

        <div class="w-100">
            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-sm btn-light-primary" id="service_fee_add_row">
                    <i class="ki-outline ki-plus fs-4"></i>
                    @lang('establishment::general.add_service_fee')
                </button>
            </div>

            <div class="d-flex flex-column gap-4" id="service_fee_rows">
                @foreach ($rows as $index => $row)
                    @include('establishment::components.establishments.partials.service-fee-row', [
                        'index' => $index,
                        'row' => $row,
                        'diningTypes' => $diningTypes,
                        'cashierPaymentRows' => $cashierPaymentRows,
                        'locale' => $locale,
                    ])
                @endforeach
            </div>
        </div>
    </x-form.form-card>
</div>

<template id="service_fee_row_template">
    @include('establishment::components.establishments.partials.service-fee-row', [
        'index' => '__INDEX__',
        'row' => [
            'id' => null,
            'name_ar' => '',
            'name_en' => '',
            'service_fee_type' => '0',
            'amount' => null,
            'application_type' => '1',
            'calculation_method' => '0',
            'taxable' => false,
            'active' => true,
            'auto_apply_type' => '',
            'dining_type_ids' => [],
            'guestCount' => null,
            'credit_type' => null,
            'from_date' => null,
            'to_date' => null,
        ],
        'diningTypes' => $diningTypes,
        'cashierPaymentRows' => $cashierPaymentRows,
        'locale' => $locale,
    ])
</template>
