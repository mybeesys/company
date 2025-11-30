@extends('layouts.app')

@section('title', __('menuItemLang.reports_list'))

@section('content')
    <div class="card card-flush h-md-100">
        <div class="card-header">
            <div class="card-title">
                <h2>@lang('menuItemLang.reports_list')</h2>
            </div>
        </div>
        <div class="card-body">
            <h3 class="mt-4 mb-3">@lang('report::purchase.sell_reports')</h3>
            <div class="row g-5 g-xl-8">
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('sell-payment-report') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-warning"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.sell-payment-report')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.sell_payment_report_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('product-sales-report') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-warning"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-sales-report')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.product_sales_report_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>



            <h3 class="mt-4 mb-3">@lang('report::purchase.purchase_reports')</h3>
            <div class="row g-5 g-xl-8">
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('purchase-payment-report') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-primary"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.purchase-payment-report')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.purchase_payment_report_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('product-purchase-report') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-primary"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-purchase-report')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.product_purchase_report_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            {{-- <h3 class="mt-4 mb-3">@lang('report::purchase.register_reports')</h3> --}}



            <h3 class="mt-4 mb-3">@lang('report::purchase.inventory_reports')</h3>
            <div class="row g-5 g-xl-8">
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('product-inventory-report') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-success"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-inventory-report')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.product_inventory_report_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('product-inventory-summary') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-success"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-inventory-summary')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.product-inventory-summary_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('Product-Stock-Report') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line bg-success"></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-inventory')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.product-inventory-summary_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>



            <h3 class="mt-4 mb-3">@lang('report::purchase.others_reports')</h3>
            <div class="row g-5 g-xl-8">
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('Profit-Loss') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line "></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.Profit-Loss')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.profit-loss_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                    <a href="{{ route('purchase-sell') }}"
                        class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                        <div class="card-body d-flex p-0">
                            <div class="report-color-line "></div>
                            <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.purchase-sell')</h4>
                                <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="@lang('report::purchase.purchase_sell_details')">
                                    <i class="bi bi-question-circle text-muted fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                    <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                        <a href="{{ url('Register-Report') }}"
                            class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                            <div class="card-body d-flex p-0">
                                <div class="report-color-line bg-info"></div>
                                <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                                    <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('report::fields.register_report')</h4>
                                    <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="@lang('report::fields.register_report_details')">
                                        <i class="bi bi-question-circle text-muted fs-4"></i>
                                    </div>
                                </div>
                            </div>
                        </a>

                </div>
            </div>
        </div>
    </div>

    <style>
        .report-color-line {
            width: 6px;
            min-height: 100%;
            border-top-left-radius: 0.625rem;
            border-bottom-left-radius: 0.725rem;
        }

        .report-info-icon {
            cursor: pointer;
        }

        .bg-blue-yellow {
            background: linear-gradient(to right, #0d6efd 50%, #ffc107 50%);
            width: 6px;
            min-height: 100%;
            border-top-left-radius: 0.625rem;
            border-bottom-left-radius: 0.725rem;
        }
    </style>
@endsection

@section('script')
    @parent
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
@endsection
