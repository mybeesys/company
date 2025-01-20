@extends('layouts.app')

@section('title', __('accounting::lang.accounting_dashboard'))
@section('css')
    <style>
        .card-p {
            padding: 1rem 2.25rem !important;
        }

        .pe-11 {
            padding-right: 1.75rem !important;
        }
    </style>
@stop
@section('content')

    <div class="container">
        <div class="row">
            <div class="col-6">
                <div class="d-flex align-items-center gap-2 gap-lg-3">
                    {{-- <a href="#" class="btn btn-flex btn-primary h-40px fs-7 fw-bold" data-bs-toggle="modal"
                        data-bs-target="#kt_modal_create_campaign">
                        @lang('accounting::lang.import_tree_of_accounts')
                    </a> --}}
                    <h1> @lang('accounting::lang.accounting_dashboard')</h1>

                </div>
            </div>
            <div class="col-6" style="justify-content: end;display: flex;">


            </div>
        </div>
    </div>



    <div class="col-xl-12" @if (app()->getLocale() == 'ar') dir="rtl" @endif>


        <div class="card card-xl-stretch mb-xl-8 mt-2">

            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">@lang('accounting::lang.Overview_tree_Accounts')</span>

                    <span class="text-muted fw-semibold fs-7">@lang('accounting::lang.Overview_tree_Accounts_descrption')</span>
                </h3>

            </div>

            <div class="card-body p-0 d-flex flex-column">

                <div class="card-p pt-0 bg-body flex-grow-1">

                    <div class="d-flex flex-column flex-grow-1 ">

                        <div class="d-flex flex-wrap">

                            @foreach ($account_types as $k => $v)
                                @php
                                    $bal = 0;
                                    foreach ($tree_of_account_overview as $overview) {
                                        if ($overview->account_primary_type == $k && !empty($overview->balance)) {
                                            $bal = (float) $overview->balance;
                                        }
                                    }
                                @endphp


                                <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">

                                    <div class="d-flex align-items-center">

                                        <div class="fs-2 fw-bold counted" data-kt-countup="true"
                                            data-kt-countup-value="4500" data-kt-countup-prefix="$" data-kt-initialized="1">
                                            {{ $bal }}<span
                                                class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span>
                                        </div>
                                    </div>

                                    <div class="fw-semibold fs-6 text-gray-500"> {{ $v['label'] }} @if ($bal < 0)
                                            {{ in_array($v['label'], ['asset', 'expenses']) ? ' (CR)' : ' (DR)' }}
                                        @endif
                                    </div>

                                </div>
                            @endforeach

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <div class="col-lg-6">
            <!--begin::Summary-->
            <div class="card card-flush h-lg-100">

                <div class="card-header mt-2">

                    <div class="card-title flex-column">
                        <h3 class="fw-bold ">@lang('accounting::lang.chart_of_accounts')</h3>
                    </div>

                </div>

                <div class="card-body p-9 pt-5">

                    <div class="d-flex flex-wrap">

                        <div class="position-relative d-flex flex-center h-175px w-175px me-15 mb-7"
                            style="margin-left: 0.7rem !important;">
                            <div class="position-absolute translate-middle start-50 top-50 d-flex flex-column flex-center">
                                <span class="fs-2qx fw-bold">{{ $total_blance }}</span>
                                <span class="fs-6 fw-semibold text-gray-500">@lang('messages.total')</span>
                            </div>

                            <canvas id="project_overview_chart" width="175" height="175"
                                style="display: block; box-sizing: border-box; height: 175px; width: 175px;"></canvas>
                        </div>
                        <!--end::Chart-->

                        <!--begin::Labels-->
                        <div class="d-flex flex-column justify-content-center flex-row-fluid pe-11 mb-5">

                            @foreach ($account_types as $k => $v)
                                @php
                                    $bal = 0;
                                    foreach ($tree_of_account_overview as $overview) {
                                        if ($overview->account_primary_type == $k && !empty($overview->balance)) {
                                            $bal = (float) $overview->balance;
                                        }
                                    }
                                @endphp

                                <div class="d-flex fs-6 fw-semibold align-items-center mb-3">
                                    <div class="bullet bg-primary me-3"
                                        style="background-color: {{ $v['color'] }} !important;"></div>
                                    <div class="text-gray-500">{{ $v['label'] }} @if ($bal < 0)
                                            {{ in_array($v['label'], ['asset', 'expenses']) ? ' (CR)' : ' (DR)' }}
                                        @endif
                                    </div>
                                    <div class="ms-auto fw-bold text-gray-700">{{ $bal }} <span
                                            class="fw-semibold mx-2 text-muted fs-7">@get_format_currency()</span></div>
                                </div>
                            @endforeach
                        </div>
                        <!--end::Labels-->
                    </div>
                    <!--end::Wrapper-->



                    <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed  p-6">


                        <div class="d-flex flex-stack flex-grow-1 ">

                            <div class=" fw-semibold">

                                <div class="fs-6 text-gray-700 "><a class="fw-bold me-1">
                                        @ </a>@lang('accounting::lang.chart_nots')</div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        {{-- <div class="card card-xl-stretch mb-5 mb-xl-8">
            <!--begin::Header-->
            <div class="card-header border-0">
                <h3 class="card-title fw-bold text-gray-900">Notifications</h3>

                <div class="card-toolbar">
                    <!--begin::Menu-->
                    <button type="button" class="btn btn-sm btn-icon btn-color-primary btn-active-light-primary"
                        data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-category fs-6"></i> </button>


                </div>
            </div>
            <!--end::Header-->

            <!--begin::Body-->
            <div class="card-body pt-0">
                <!--begin::Item-->
                <div class="d-flex align-items-center bg-light-warning rounded p-5 mb-7">
                    <i class="ki-outline ki-abstract-26 text-warning fs-1 me-5"></i>
                    <!--begin::Title-->
                    <div class="flex-grow-1 me-2">
                        <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">Group lunch
                            celebration</a>

                        <span class="text-muted fw-semibold d-block">Due in 2 Days</span>
                    </div>
                    <!--end::Title-->

                    <!--begin::Lable-->
                    <span class="fw-bold text-warning py-1">+28%</span>
                    <!--end::Lable-->
                </div>
                <!--end::Item-->
                <!--begin::Item-->
                <div class="d-flex align-items-center bg-light-success rounded p-5 mb-7">
                    <i class="ki-outline ki-abstract-26 text-success fs-1 me-5"></i>
                    <!--begin::Title-->
                    <div class="flex-grow-1 me-2">
                        <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">Navigation
                            optimization</a>

                        <span class="text-muted fw-semibold d-block">Due in 2 Days</span>
                    </div>
                    <!--end::Title-->

                    <!--begin::Lable-->
                    <span class="fw-bold text-success py-1">+50%</span>
                    <!--end::Lable-->
                </div>
                <!--end::Item-->
                <!--begin::Item-->
                <div class="d-flex align-items-center bg-light-danger rounded p-5 mb-7">
                    <i class="ki-outline ki-abstract-26 text-danger fs-1 me-5"></i>
                    <!--begin::Title-->
                    <div class="flex-grow-1 me-2">
                        <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">Rebrand strategy
                            planning</a>

                        <span class="text-muted fw-semibold d-block">Due in 5 Days</span>
                    </div>
                    <!--end::Title-->

                    <!--begin::Lable-->
                    <span class="fw-bold text-danger py-1">-27%</span>
                    <!--end::Lable-->
                </div>
                <!--end::Item-->
                <!--begin::Item-->
                <div class="d-flex align-items-center bg-light-info rounded p-5 ">
                    <i class="ki-outline ki-abstract-26 text-info fs-1 me-5"></i>
                    <!--begin::Title-->
                    <div class="flex-grow-1 me-2">
                        <a href="#" class="fw-bold text-gray-800 text-hover-primary fs-6">Product goals strategy</a>

                        <span class="text-muted fw-semibold d-block">Due in 7 Days</span>
                    </div>
                    <!--end::Title-->

                    <!--begin::Lable-->
                    <span class="fw-bold text-info py-1">+8%</span>
                    <!--end::Lable-->
                </div>
                <!--end::Item-->

            </div>
            <!--end::Body-->
        </div> --}}



    @stop

    @section('script')
        <script>
            var ctx = document.getElementById('project_overview_chart').getContext('2d');
            const accountingPrimaryType = @json($account_types);
            const labels = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].label);
            const data = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].GLC);
            const color = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].color);
            const balance = Object.keys(accountingPrimaryType).map(key => accountingPrimaryType[key].balance);
            console.log(balance);

            var projectOverviewChart = new Chart(ctx, {
                type: 'pie', // 'line', 'bar', 'pie', 'doughnut'
                data: {
                    labels: labels,
                    datasets: [{
                        data: balance,
                        backgroundColor: color,
                    }]
                },
                options: {
                    responsive: true,

                    cutout: '80%',
                    plugins: {
                        legend: {
                            position: false,
                        }
                    }
                }
            });
        </script>
    @stop
