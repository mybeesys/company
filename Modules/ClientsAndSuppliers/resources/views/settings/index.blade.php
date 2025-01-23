@extends('layouts.app')

@section('title', __('menuItemLang.general_setting'))

@section('css')
    @if (session('locale') == 'ar')
        <style>
            input[type="number"]:not(.numInput) {
                text-align: right;
            }

            input[type="number"]::-webkit-input-placeholder,
            input[type="email"]::-webkit-input-placeholder {
                text-align: right;
            }

            @media (min-width: 768px) {
                .text-md-nowrap {
                    white-space: nowrap !important;
                }
            }
        </style>
    @endif
@endsection
@section('content')

    <div class="d-flex flex-column flex-row-fluid gap-5">
        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-4 border-0 fw-bold">
            <li class="nav-item nav-link-taxes">
                <a class="nav-link justify-content-center text-active-gray-800 active" data-bs-toggle="tab"
                    href="#loyalty_points_settings_tab">@lang('clientsandsuppliers::general.clients_loyalty_points_settings')</a>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="loyalty_points_settings_tab" role="tabpanel">
                <x-form.form-card class="p-5" :headerDiv="false">
                    <form action="#" id="loyalty_points_settings_form">
                        @csrf
                        <x-form.form-card class="mb-15 " :title="__('clientsandsuppliers::general.loyalty_points')" :collapsible="true"
                            id="loyalty_points_settings_field" headerClass="loyalty_points_settings_active_field">

                            <x-slot:header>
                                <div class="card-toolbar justify-content-end">
                                    <x-form.switch-div class="form-check-custom">
                                        <input type="hidden" name="key[loyalty_points_settings_active]" value="0">
                                        <x-form.input :errors=$errors class="form-check-input h-20px w-30px" value="1"
                                            type="checkbox" name="key[loyalty_points_settings_active]" />
                                    </x-form.switch-div>
                                </div>
                            </x-slot:header>

                            <x-form.form-card class="mb-5" :title="__('clientsandsuppliers::general.points_earning_settings')">
                                <div class="d-flex flex-wrap mt-10">
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input :errors=$errors :labelWidth="true" attribute="step=.01"
                                            :hint="__(
                                                'clientsandsuppliers::general.amount_to_pay_to_earn_point_hint',
                                            )"
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'amount_to_pay_to_earn_point')?->value }}"
                                            type="number" name="key[amount_to_pay_to_earn_point]" :label="__('clientsandsuppliers::fields.amount_to_pay_to_earn_point')" />
                                    </x-form.input-div>
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input type="number" :errors=$errors attribute="step=.01"
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'minimum_order_payment_to_earn_points')?->value }}"
                                            :labelWidth="true" :hint="__(
                                                'clientsandsuppliers::general.minimum_order_payment_to_earn_points_hint',
                                            )"
                                            name="key[minimum_order_payment_to_earn_points]" :label="__(
                                                'clientsandsuppliers::fields.minimum_order_payment_to_earn_points',
                                            )" />
                                    </x-form.input-div>
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input :errors=$errors type="number" attribute="step=.01" :labelWidth="true"
                                            :hint="__('clientsandsuppliers::general.maximum_order_points_hint')"
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'maximum_order_points')?->value }}"
                                            name="key[maximum_order_points]" :label="__('clientsandsuppliers::fields.maximum_order_points')" />
                                    </x-form.input-div>
                                </div>
                            </x-form.form-card>

                            <x-form.form-card :title="__('clientsandsuppliers::general.points_redemption_settings')">
                                <div class="d-flex flex-wrap mt-10">
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input :errors=$errors :labelWidth="true" type="number" attribute="step=.01"
                                            :hint="__(
                                                'clientsandsuppliers::general.redeemed_amount_for_each_point_hint',
                                            )"
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'redeemed_amount_for_each_point')?->value }}"
                                            name="key[redeemed_amount_for_each_point]" :label="__('clientsandsuppliers::fields.redeemed_amount_for_each_point')" />
                                    </x-form.input-div>
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input :errors=$errors
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'minimum_order_payment_to_redeem_points')?->value }}"
                                            :labelWidth="true" type="number" attribute="step=.01" :hint="__(
                                                'clientsandsuppliers::general.minimum_order_payment_to_redeem_points_hint',
                                            )"
                                            name="key[minimum_order_payment_to_redeem_points]" :label="__(
                                                'clientsandsuppliers::fields.minimum_order_payment_to_redeem_points',
                                            )" />
                                    </x-form.input-div>
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input :errors=$errors :labelWidth="true" :hint="__('clientsandsuppliers::general.minimum_points_hint')"
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'minimum_points')?->value }}"
                                            name="key[minimum_points]" :label="__('clientsandsuppliers::fields.minimum_points')" type="number"
                                            attribute="step=.01" />
                                    </x-form.input-div>
                                </div>

                                <div class="d-flex mt-10">
                                    <x-form.input-div class="text-md-nowrap mb-10 w-100 px-2">
                                        <x-form.input :errors=$errors
                                            value="{{ $loyaltyPointsSettings->firstWhere('key', 'maximum_redeem_point_per_order')?->value }}"
                                            :labelWidth="true" type="number" attribute="step=.01" :hint="__(
                                                'clientsandsuppliers::general.maximum_redeem_point_per_order_hint',
                                            )"
                                            name="key[maximum_redeem_point_per_order]" :label="__('clientsandsuppliers::fields.maximum_redeem_point_per_order')" />
                                    </x-form.input-div>
                                    <x-form.input-div class="text-md-nowrap d-flex flex-column">
                                        <div class="form-label">
                                            <label for="points_expiration_period">@lang('clientsandsuppliers::fields.points_expiration_period')</label>
                                            @include('components.form.field-hint', [
                                                'hint' => __(
                                                    'clientsandsuppliers::general.points_expiration_period_hint'),
                                            ])
                                        </div>
                                        <div class="input-group input-group-solid mb-5 flex-nowrap">
                                            <x-form.input :label="false" class="border-0 w-100" :errors=$errors
                                                type="number" attribute="step=.01" :labelWidth="true"
                                                value="{{ $loyaltyPointsSettings->firstWhere('key', 'points_expiration_period')?->value }}"
                                                name="key[points_expiration_period]" />
                                            <x-form.select name="key[points_expiration_period_type]"
                                                class="w-50 rounded-0 border-3 border-start" :options="[
                                                    ['id' => 'year', 'name' => __('employee::fields.year')],
                                                    ['id' => 'month', 'name' => __('employee::fields.month')],
                                                ]"
                                                value="{{ $loyaltyPointsSettings->firstWhere('key', 'points_expiration_period_type')?->value }}"
                                                :errors="$errors" data_allow_clear="false" />
                                        </div>
                                    </x-form.input-div>
                                </div>
                            </x-form.form-card>
                        </x-form.form-card>
                        <x-form.form-buttons cancelUrl="{{ url('/employee') }}" id="loyalty_points_settings_form" />

                    </form>
                </x-form.form-card>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @parent
    <script src="{{ url('modules/employee/js/messages.js') }}"></script>

    <script>
        $(document).ready(function() {
            loyaltySettings();
            loyaltyPointsForm();
            $('select[name="key[points_expiration_period_type]"]').select2({
                minimumResultsForSearch: -1
            });
        });

        function loyaltyPointsForm() {
            $('#loyalty_points_settings_form').on('submit', function(e) {
                e.preventDefault();
                ajaxRequest("{{ route('store-loyalty-point-settings') }}", "POST", $(this).serializeArray()).fail(
                    function(data) {
                        $.each(data.responseJSON.errors, function(key, value) {
                            $(`[name='${key}']`).addClass('is-invalid');
                            $(`[name='${key}']`).after('<div class="invalid-feedback">' + value +
                                '</div>');
                        });
                    });
            });
        }

        function loyaltySettings() {

            let saveButton = $(`#loyalty_points_settings_form_button`);
            if ("{{ $loyaltyPointsSettings->firstWhere('key', 'loyalty_points_settings_active')?->value }}" === "1") {
                $('#loyalty_points_settings_field').collapse('toggle');
                $('input[name="key[loyalty_points_settings_active]"]').prop('checked', true).val(1);

                $('[name="key[amount_to_pay_to_earn_point]"], [name="key[redeemed_amount_for_each_point]"]').each(
                    function() {
                        $(this).prop('required', true);
                    });
            }

            $('.loyalty_points_settings_active_field').on('click', function(e) {
                if ($(this).attr("aria-expanded") == 'true') {
                    $('input[name="key[loyalty_points_settings_active]"]').prop('checked', true);
                    $('input[name="key[loyalty_points_settings_active]"]').val(1);
                    console.log($('[name^="key*"]'));

                    $('[name="key[amount_to_pay_to_earn_point]"], [name="key[redeemed_amount_for_each_point]"]')
                        .each(function() {
                            $(this).prop('required', true);
                        });
                } else {
                    $('input[name="key[loyalty_points_settings_active]"]').prop('checked', false);
                    $('input[name="key[loyalty_points_settings_active]"]').val(0);
                    $('[name="key[amount_to_pay_to_earn_point]"], [name="key[redeemed_amount_for_each_point]"]')
                        .each(function() {
                            $(this).prop('required', false);
                        });
                    checkErrors(saveButton);
                }
            });
        }
    </script>
@endsection
