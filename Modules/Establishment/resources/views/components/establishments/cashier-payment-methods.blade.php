@props([
    'accounts' => null,
    'cashierPaymentRows' => [],
    'branchOptions' => null,
])
@php
    $locale = app()->getLocale();
    $rows = old('cashier_payment_rows', $cashierPaymentRows ?? []);
    if (! is_array($rows) || $rows === []) {
        $rows = [['id' => null, 'name_ar' => '', 'name_en' => '', 'account_id' => null, 'establishment_ids' => [], 'branch_accounts' => []]];
    }
@endphp
<div class="establishment-cashier-payments d-flex flex-column flex-row-fluid gap-7 gap-lg-10" id="cashier_payment_methods_root">
    <x-form.form-card bodyClass="d-flex flex-column gap-5" :title="__('establishment::general.cashier_payment_methods')">
        <div class="w-100">
            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-sm btn-light-primary" id="cashier_add_payment_row">
                    <i class="ki-outline ki-plus fs-4"></i>
                    @lang('establishment::general.add_cashier_payment_method')
                </button>
            </div>

            <div class="d-flex flex-column gap-4" id="cashier_payment_rows">
                @foreach ($rows as $index => $row)
                    @include('establishment::components.establishments.partials.cashier-payment-row', [
                        'index' => $index,
                        'row' => $row,
                        'accounts' => $accounts,
                        'locale' => $locale,
                        'branchOptions' => $branchOptions,
                    ])
                @endforeach
            </div>
        </div>
    </x-form.form-card>
</div>

<template id="cashier_branch_account_row_template">
    @include('establishment::components.establishments.partials.branch-account-row', [
        'index' => '__INDEX__',
        'estId' => '__EST_ID__',
        'namePrefix' => 'cashier_payment_rows',
        'accounts' => $accounts,
        'accountId' => null,
        'branchName' => '__BRANCH_NAME__',
        'locale' => $locale,
    ])
</template>

<template id="cashier_payment_row_template">
    @include('establishment::components.establishments.partials.cashier-payment-row', [
        'index' => '__INDEX__',
        'row' => ['id' => null, 'name_ar' => '', 'name_en' => '', 'account_id' => null, 'establishment_ids' => [], 'branch_accounts' => []],
        'accounts' => $accounts,
        'locale' => $locale,
        'branchOptions' => $branchOptions,
    ])
</template>
