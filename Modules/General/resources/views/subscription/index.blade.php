@extends('layouts.app')

@section('title', __('general.subscriptions'))

@section('content')

    <div class="d-flex flex-column flex-lg-row">
        <!--begin::Content-->
        <div class="flex-lg-row-fluid me-lg-15 order-2 order-lg-1 mb-10 mb-lg-0">
            <x-form.form-card class="pt-3 mb-5 mb-xl-10" :title="__('general::general.subscription_details')">
                <div class="mb-10">
                    <x-form.form-card class="pt-3" title="{{ __('general::general.billing_address') }}:">
                        <div class="d-flex flex-wrap py-5">
                            <div class="flex-equal me-5">
                                <table class="table table-striped table-row-bordered fs-6 fw-semibold gs-0 gy-2 gx-2 m-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-gray-500 min-w-175px w-175px">@lang('general::general.bill_to'):</td>
                                            <td class="text-gray-800 min-w-200px">
                                                <a href="pages/apps/customers/view.html"
                                                    class="text-gray-800 text-hover-primary">-----------</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-500">@lang('general::general.customer_name'):</td>
                                            <td class="text-gray-800">-----------</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-500">@lang('general::general.address'):</td>
                                            <td class="text-gray-800">-----------</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-500">@lang('employee::fields.phone'):</td>
                                            <td class="text-gray-800">-----------</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex-equal">
                                <table class="table table-striped table-row-bordered  fs-6 fw-semibold gs-0 gy-2 gx-2 m-0">
                                    <tbody>
                                        <tr>
                                            <td class="text-gray-500 min-w-175px w-175px">@lang('general::general.subscribed_to_plan'):</td>
                                            <td class="text-gray-800 min-w-200px">
                                                <a href="#" class="text-gray-800 text-hover-primary">-----------</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-500">@lang('general::general.subscription_fees'):</td>
                                            <td class="text-gray-800">-----------</td>
                                        </tr>
                                        <tr>
                                            <td class="text-gray-500">@lang('general::general.subscription_type'):</td>
                                            <td class="text-gray-800">-----------</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-form.form-card>
                </div>
                <div class="mb-0">
                    <x-form.form-card class="pt-3 mb-5" title="{{ __('general::general.current_plan') }}:">
                        <x-slot:header>
                            <a href="{{ str_replace(tenant('id') . '.', request()->secure() ? 'https://' : 'http://', tenant()->domains->first()->domain) }}" class="btn btn-bg-warning my-auto text-white">@lang('general::general.change_the_plan')</a>
                        </x-slot:header>
                        <div class="mb-5 d-flex gap-2">
                            <h3 class="text-gray-700 fs-2">@lang('employee::fields.name'):</h3>
                            <h3 class="text-gray-600 fs-2">
                                {{ session('locale') === 'ar' ? $current_subscription->plan->name_ar : $current_subscription->plan->name }}
                            </h3>
                        </div>
                        <div class="mb-5 d-flex gap-2">
                            <h3 class="text-gray-700 fs-2">@lang('general::general.start_subscription_date'):</h3>
                            <h3 class="text-gray-600 fs-2">
                                {{ date_format($current_subscription->started_at, 'Y-m-d') }}
                            </h3>
                        </div>
                        <div class="mb-5 d-flex gap-2">
                            <h3 class="text-gray-700 fs-2">@lang('general::general.end_subscription_date'):</h3>
                            <h3 class="text-gray-600 fs-2">
                                {{ date_format($current_subscription->expired_at, 'Y-m-d') }}
                            </h3>
                        </div>
                        <h3 class="text-gray-700 fs-2">@lang('employee::main.features'):</h3>
                        <div class="d-flex flex-wrap gap-4 mt-5">
                            @foreach ($current_subscription->plan->features as $feature)
                                <div
                                    class="border border-dashed border-gray-300 text-center min-w-125px rounded p-5 my-auto">
                                    <span
                                        class="fs-4 fw-semibold text-success d-block">{{ session('locale') === 'ar' ? $feature->name_ar : $feature->name_en }}
                                        @if ($feature->consumable)
                                            : {{ (int) $feature->pivot->charges }}
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                    </x-form.form-card>

                </div>
                <x-form.form-card class="pt-3 mb-5" title="{{ __('general::general.previous_subscriptions') }}:">

                    <div class="table-responsive">
                        <table class="table table-striped table-row-bordered align-middle fs-6 gy-4 mb-0">
                            <thead>
                                <tr
                                    class="border-bottom border-gray-200 text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th class="px-4 min-w-150px">@lang('employee::fields.plan')</th>
                                    <th class="px-4 min-w-125px">@lang('general::general.start_subscription_date')</th>
                                    <th class="px-4 min-w-125px">@lang('general::general.end_subscription_date')</th>
                                    <th class="px-4 min-w-125px">@lang('employee::fields.suppressed_at')</th>
                                    <th class="text-middle px-4 min-w-70px">@lang('messages.actions')</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-800">
                                @foreach ($old_subscriptions as $old_subscription)
                                    <tr>
                                        <td class="px-4">
                                            <label
                                                class="w-150px">{{ session('locale') === 'ar' ? $old_subscription->plan->name_ar : $old_subscription->plan->name }}</label>
                                        </td>
                                        <td class="px-4">
                                            <span
                                                class="badge badge-light-success">{{ date_format($old_subscription->started_at, 'Y-m-d') }}</span>
                                        </td>
                                        <td class="px-4"><span
                                                class="badge badge-light-primary">{{ date_format($old_subscription->expired_at, 'Y-m-d') }}</span>
                                        </td>
                                        <td class="px-4">
                                            <span
                                                class="badge badge-light-danger">{{ $old_subscription->suppressed_at ? date_format($old_subscription->suppressed_at, 'Y-m-d') : '-' }}</span>
                                        </td>
                                        <td class="text-middle px-4">
                                            <a href="#" class="btn btn-icon btn-active-light-primary w-30px h-30px"
                                                data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                                <i class="ki-outline ki-setting-3 fs-3"></i>
                                            </a>
                                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-200px py-4"
                                                data-kt-menu="true">
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3">Pause Subscription</a>
                                                </div>
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link px-3"
                                                        data-kt-subscriptions-view-action="delete">Edit Subscription</a>
                                                </div>
                                                <div class="menu-item px-3">
                                                    <a href="#" class="menu-link text-danger px-3"
                                                        data-kt-subscriptions-view-action="edit">Cancel Subscription</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                            <!--end::Table body-->
                        </table>
                        <!--end::Table-->
                    </div>
                </x-form.form-card>
                <!--end::Product table-->
        </div>
        </x-form.form-card>
        <x-form.form-card class="pt-3 mb-5 mb-xl-10" :title="__('general::general.invoices')">
            <x-slot:header>
                <div class="card-toolbar">
                    <!--begin::Tab nav-->
                    <ul class="nav nav-stretch fs-5 fw-semibold nav-line-tabs nav-line-tabs-2x border-transparent"
                        role="tablist">
                        <li class="nav-item" role="presentation">
                            <a id="kt_referrals_year_tab" class="nav-link text-active-primary active" data-bs-toggle="tab"
                                role="tab" href="#kt_customer_details_invoices_1" aria-selected="true">This Year</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a id="kt_referrals_2019_tab" class="nav-link text-active-primary ms-3" data-bs-toggle="tab"
                                role="tab" href="#kt_customer_details_invoices_2" aria-selected="false"
                                tabindex="-1">2020</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a id="kt_referrals_2018_tab" class="nav-link text-active-primary ms-3" data-bs-toggle="tab"
                                role="tab" href="#kt_customer_details_invoices_3" aria-selected="false"
                                tabindex="-1">2019</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a id="kt_referrals_2017_tab" class="nav-link text-active-primary ms-3" data-bs-toggle="tab"
                                role="tab" href="#kt_customer_details_invoices_4" aria-selected="false"
                                tabindex="-1">2018</a>
                        </li>
                    </ul>
                    <!--end::Tab nav-->
                </div>
            </x-slot:header>
            <div id="kt_referred_users_tab_content" class="tab-content">
                <!--begin::Tab panel-->
                <div id="kt_customer_details_invoices_1" class="tab-pane fade show active" role="tabpanel"
                    aria-labelledby="kt_referrals_year_tab">
                    <!--begin::Table wrapper-->
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table id="kt_customer_details_invoices_table_1"
                            class="table align-middle table-row-dashed fs-6 fw-bold gs-0 gy-4 p-0 m-0">
                            <thead class="border-bottom border-gray-200 fs-7 text-uppercase fw-bold">
                                <tr class="text-start text-gray-500">
                                    <th class="min-w-100px">Order ID</th>
                                    <th class="min-w-100px">Amount</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-125px">Date</th>
                                    <th class="w-100px">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="fs-6 fw-semibold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">102445788</a>
                                    </td>
                                    <td class="text-success">$38.00</td>
                                    <td>
                                        <span class="badge badge-light-info">In progress</span>
                                    </td>
                                    <td>Nov 01, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">423445721</a>
                                    </td>
                                    <td class="text-danger">$-2.60</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Oct 24, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                    </td>
                                    <td class="text-success">$76.00</td>
                                    <td>
                                        <span class="badge badge-light-info">In progress</span>
                                    </td>
                                    <td>Oct 08, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                    </td>
                                    <td class="text-success">$5.00</td>
                                    <td>
                                        <span class="badge badge-light-success">Approved</span>
                                    </td>
                                    <td>Sep 15, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">523445943</a>
                                    </td>
                                    <td class="text-danger">$-1.30</td>
                                    <td>
                                        <span class="badge badge-light-info">In progress</span>
                                    </td>
                                    <td>May 30, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Tab panel-->
                <!--begin::Tab panel-->
                <div id="kt_customer_details_invoices_2" class="tab-pane fade" role="tabpanel"
                    aria-labelledby="kt_referrals_2019_tab">
                    <!--begin::Table wrapper-->
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table id="kt_customer_details_invoices_table_2"
                            class="table align-middle table-row-dashed fs-6 fw-bold gs-0 gy-4 p-0 m-0">
                            <thead class="border-bottom border-gray-200 fs-7 text-uppercase fw-bold">
                                <tr class="text-start text-gray-500">
                                    <th class="min-w-100px">Order ID</th>
                                    <th class="min-w-100px">Amount</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-125px">Date</th>
                                    <th class="w-100px">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="fs-6 fw-semibold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">523445943</a>
                                    </td>
                                    <td class="text-danger">$-1.30</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>May 30, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">231445943</a>
                                    </td>
                                    <td class="text-success">$204.00</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Apr 22, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">426445943</a>
                                    </td>
                                    <td class="text-success">$31.00</td>
                                    <td>
                                        <span class="badge badge-light-success">Approved</span>
                                    </td>
                                    <td>Feb 09, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">984445943</a>
                                    </td>
                                    <td class="text-success">$52.00</td>
                                    <td>
                                        <span class="badge badge-light-success">Approved</span>
                                    </td>
                                    <td>Nov 01, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">324442313</a>
                                    </td>
                                    <td class="text-danger">$-0.80</td>
                                    <td>
                                        <span class="badge badge-light-info">In progress</span>
                                    </td>
                                    <td>Jan 04, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Tab panel-->
                <!--begin::Tab panel-->
                <div id="kt_customer_details_invoices_3" class="tab-pane fade" role="tabpanel"
                    aria-labelledby="kt_referrals_2018_tab">
                    <!--begin::Table wrapper-->
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table id="kt_customer_details_invoices_table_3"
                            class="table align-middle table-row-dashed fs-6 fw-bold gs-0 gy-4 p-0 m-0">
                            <thead class="border-bottom border-gray-200 fs-7 text-uppercase fw-bold">
                                <tr class="text-start text-gray-500">
                                    <th class="min-w-100px">Order ID</th>
                                    <th class="min-w-100px">Amount</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-125px">Date</th>
                                    <th class="w-100px">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="fs-6 fw-semibold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">426445943</a>
                                    </td>
                                    <td class="text-success">$31.00</td>
                                    <td>
                                        <span class="badge badge-light-info">In progress</span>
                                    </td>
                                    <td>Feb 09, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">984445943</a>
                                    </td>
                                    <td class="text-success">$52.00</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Nov 01, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">324442313</a>
                                    </td>
                                    <td class="text-danger">$-0.80</td>
                                    <td>
                                        <span class="badge badge-light-success">Approved</span>
                                    </td>
                                    <td>Jan 04, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">312445984</a>
                                    </td>
                                    <td class="text-success">$5.00</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Sep 15, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">102445788</a>
                                    </td>
                                    <td class="text-success">$38.00</td>
                                    <td>
                                        <span class="badge badge-light-info">In progress</span>
                                    </td>
                                    <td>Nov 01, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Tab panel-->
                <!--begin::Tab panel-->
                <div id="kt_customer_details_invoices_4" class="tab-pane fade" role="tabpanel"
                    aria-labelledby="kt_referrals_2017_tab">
                    <!--begin::Table wrapper-->
                    <div class="table-responsive">
                        <!--begin::Table-->
                        <table id="kt_customer_details_invoices_table_4"
                            class="table align-middle table-row-dashed fs-6 fw-bold gs-0 gy-4 p-0 m-0">
                            <thead class="border-bottom border-gray-200 fs-7 text-uppercase fw-bold">
                                <tr class="text-start text-gray-500">
                                    <th class="min-w-100px">Order ID</th>
                                    <th class="min-w-100px">Amount</th>
                                    <th class="min-w-100px">Status</th>
                                    <th class="min-w-125px">Date</th>
                                    <th class="w-100px">Invoice</th>
                                </tr>
                            </thead>
                            <tbody class="fs-6 fw-semibold text-gray-600">
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">102445788</a>
                                    </td>
                                    <td class="text-success">$38.00</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Nov 01, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">423445721</a>
                                    </td>
                                    <td class="text-danger">$-2.60</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Oct 24, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">102445788</a>
                                    </td>
                                    <td class="text-success">$38.00</td>
                                    <td>
                                        <span class="badge badge-light-success">Approved</span>
                                    </td>
                                    <td>Nov 01, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">423445721</a>
                                    </td>
                                    <td class="text-danger">$-2.60</td>
                                    <td>
                                        <span class="badge badge-light-success">Approved</span>
                                    </td>
                                    <td>Oct 24, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-600 text-hover-primary">426445943</a>
                                    </td>
                                    <td class="text-success">$31.00</td>
                                    <td>
                                        <span class="badge badge-light-danger">Rejected</span>
                                    </td>
                                    <td>Feb 09, 2020</td>
                                    <td class="">
                                        <button class="btn btn-sm btn-light btn-active-light-primary">Download</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!--end::Table-->
                    </div>
                    <!--end::Table wrapper-->
                </div>
                <!--end::Tab panel-->
            </div>
        </x-form.form-card>

    </div>
    <!--end::Content-->
    <!--begin::Sidebar-->
    <div class="flex-column flex-lg-row-auto w-lg-250px w-xl-300px mb-10 order-1 order-lg-2">
        <!--begin::Card-->
        <div class="card card-flush mb-0" data-kt-sticky="true" data-kt-sticky-name="subscription-summary"
            data-kt-sticky-offset="{default: false, lg: '200px'}" data-kt-sticky-width="{lg: '250px', xl: '300px'}"
            data-kt-sticky-left="auto" data-kt-sticky-top="150px" data-kt-sticky-animation="false"
            data-kt-sticky-zindex="95">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Card title-->
                <div class="card-title">
                    <h2>Summary</h2>
                </div>
                <!--end::Card title-->
                <!--begin::Card toolbar-->
                <div class="card-toolbar">
                    <!--begin::More options-->
                    <a href="#" class="btn btn-sm btn-light btn-icon" data-kt-menu-trigger="click"
                        data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-dots-square fs-3"></i>
                    </a>
                    <!--begin::Menu-->
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-6 w-200px py-4"
                        data-kt-menu="true" style="">
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3">Pause Subscription</a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link px-3" data-kt-subscriptions-view-action="delete">Edit
                                Subscription</a>
                        </div>
                        <!--end::Menu item-->
                        <!--begin::Menu item-->
                        <div class="menu-item px-3">
                            <a href="#" class="menu-link text-danger px-3"
                                data-kt-subscriptions-view-action="edit">Cancel Subscription</a>
                        </div>
                        <!--end::Menu item-->
                    </div>
                    <!--end::Menu-->
                    <!--end::More options-->
                </div>
                <!--end::Card toolbar-->
            </div>
            <!--end::Card header-->
            <!--begin::Card body-->
            <div class="card-body pt-0 fs-6">
                <!--begin::Section-->
                <div class="mb-7">
                    <!--begin::Details-->
                    <div class="d-flex align-items-center">
                        <!--begin::Avatar-->
                        <div class="symbol symbol-60px symbol-circle me-3">
                            <img alt="Pic" src="assets/media/avatars/300-5.jpg">
                        </div>
                        <!--end::Avatar-->
                        <!--begin::Info-->
                        <div class="d-flex flex-column">
                            <!--begin::Name-->
                            <a href="#" class="fs-4 fw-bold text-gray-900 text-hover-primary me-2">Sean Bean</a>
                            <!--end::Name-->
                            <!--begin::Email-->
                            <a href="#" class="fw-semibold text-gray-600 text-hover-primary">sean@dellito.com</a>
                            <!--end::Email-->
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Details-->
                </div>
                <!--end::Section-->
                <!--begin::Seperator-->
                <div class="separator separator-dashed mb-7"></div>
                <!--end::Seperator-->
                <!--begin::Section-->
                <div class="mb-7">
                    <!--begin::Title-->
                    <h5 class="mb-4">Product details</h5>
                    <!--end::Title-->
                    <!--begin::Details-->
                    <div class="mb-0">
                        <!--begin::Plan-->
                        <span class="badge badge-light-info me-2">Basic Bundle</span>
                        <!--end::Plan-->
                        <!--begin::Price-->
                        <span class="fw-semibold text-gray-600">$149.99 / Year</span>
                        <!--end::Price-->
                    </div>
                    <!--end::Details-->
                </div>
                <!--end::Section-->
                <!--begin::Seperator-->
                <div class="separator separator-dashed mb-7"></div>
                <!--end::Seperator-->
                <!--begin::Section-->
                <div class="mb-10">
                    <!--begin::Title-->
                    <h5 class="mb-4">Payment Details</h5>
                    <!--end::Title-->
                    <!--begin::Details-->
                    <div class="mb-0">
                        <!--begin::Card info-->
                        <div class="fw-semibold text-gray-600 d-flex align-items-center">Mastercard
                            <img src="assets/media/svg/card-logos/mastercard.svg" class="w-35px ms-2" alt="">
                        </div>
                        <!--end::Card info-->
                        <!--begin::Card expiry-->
                        <div class="fw-semibold text-gray-600">Expires Dec 2024</div>
                        <!--end::Card expiry-->
                    </div>
                    <!--end::Details-->
                </div>
                <!--end::Section-->
                <!--begin::Seperator-->
                <div class="separator separator-dashed mb-7"></div>
                <!--end::Seperator-->
                <!--begin::Section-->
                <div class="mb-10">
                    <!--begin::Title-->
                    <h5 class="mb-4">Subscription Details</h5>
                    <!--end::Title-->
                    <!--begin::Details-->
                    <table class="table fs-6 fw-semibold gs-0 gy-2 gx-2">
                        <!--begin::Row-->
                        <tbody>
                            <tr class="">
                                <td class="text-gray-500">Subscription ID:</td>
                                <td class="text-gray-800">sub_4567_8765</td>
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Started:</td>
                                <td class="text-gray-800">15 Apr 2021</td>
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Status:</td>
                                <td>
                                    <span class="badge badge-light-success">Active</span>
                                </td>
                            </tr>
                            <!--end::Row-->
                            <!--begin::Row-->
                            <tr class="">
                                <td class="text-gray-500">Next Invoice:</td>
                                <td class="text-gray-800">15 Apr 2022</td>
                            </tr>
                            <!--end::Row-->
                        </tbody>
                    </table>
                    <!--end::Details-->
                </div>
                <!--end::Section-->
                <!--begin::Actions-->
                <div class="mb-0">
                    <a href="apps/subscriptions/add.html" class="btn btn-primary"
                        id="kt_subscriptions_create_button">Edit Subscription</a>
                </div>
                <!--end::Actions-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->
    </div>
    <!--end::Sidebar-->
    </div>
@endsection
