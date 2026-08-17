@props([
    'accounts' => null,
    'internalConsumptionRows' => [],
    'branchOptions' => null,
])
@php
    $locale = app()->getLocale();
    $rows = old('internal_consumption_rows', $internalConsumptionRows ?? []);
    if (! is_array($rows) || $rows === []) {
        $rows = [[
            'id' => null,
            'name_ar' => '',
            'name_en' => '',
            'value_type' => 'cost',
            'value' => null,
            'account_id' => null,
            'is_active' => true,
            'establishment_ids' => [],
        ]];
    }
@endphp
<div class="establishment-internal-consumption d-flex flex-column flex-row-fluid gap-7 gap-lg-10" id="internal_consumption_types_root">
    <x-form.form-card bodyClass="d-flex flex-column gap-5" :title="__('establishment::general.internal_consumption_settings')">
        <div class="w-100">
            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-sm btn-light-primary" id="internal_consumption_add_row">
                    <i class="ki-outline ki-plus fs-4"></i>
                    @lang('establishment::general.add_internal_consumption_type')
                </button>
            </div>

            <div class="table-responsive">
                @unless (isset($branchOptions))
                    <div class="row g-3 align-items-center text-muted fw-semibold fs-7 text-uppercase d-none d-lg-flex px-2 mb-1">
                        <div class="col-lg-2">@lang('establishment::fields.name')</div>
                        <div class="col-lg-2">@lang('establishment::fields.name_en')</div>
                        <div class="col-lg-2">@lang('establishment::fields.internal_consumption_value_type')</div>
                        <div class="col-lg-1">@lang('establishment::fields.internal_consumption_value')</div>
                        <div class="col-lg-3">@lang('establishment::fields.internal_consumption_collection_account')</div>
                        <div class="col-lg-1 text-center">@lang('establishment::fields.active')</div>
                        <div class="col-lg-1 text-center">@lang('messages.delete')</div>
                    </div>
                @endunless

                <div class="d-flex flex-column gap-3" id="internal_consumption_rows">
                    @foreach ($rows as $index => $row)
                        @include('establishment::components.establishments.partials.internal-consumption-row', [
                            'index' => $index,
                            'row' => $row,
                            'accounts' => $accounts,
                            'locale' => $locale,
                            'branchOptions' => $branchOptions,
                        ])
                    @endforeach
                </div>
            </div>
        </div>
    </x-form.form-card>
</div>

<template id="internal_consumption_row_template">
    @include('establishment::components.establishments.partials.internal-consumption-row', [
        'index' => '__INDEX__',
        'row' => [
            'id' => null,
            'name_ar' => '',
            'name_en' => '',
            'value_type' => 'cost',
            'value' => null,
            'account_id' => null,
            'is_active' => true,
            'establishment_ids' => [],
        ],
        'accounts' => $accounts,
        'locale' => $locale,
        'branchOptions' => $branchOptions,
    ])
</template>
