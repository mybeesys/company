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
        <div class="row g-5 g-xl-8">
            {{-- بطاقة تقرير مدفوعات المشتريات --}}
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <a href="{{ route('purchase-payment-report') }}" class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                    <div class="card-body d-flex p-0">
                        {{-- الخط الرأسي --}}
                        <div class="report-color-line bg-primary"></div>
                        <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                            <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.purchase-payment-report')</h4>
                            <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="عرض تقارير مفصلة عن جميع مدفوعات الشراء.">
                                <i class="bi bi-question-circle text-muted fs-4"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            {{-- بطاقة تقرير مدفوعات المبيعات --}}
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <a href="{{ route('sell-payment-report') }}" class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                    <div class="card-body d-flex p-0">
                        {{-- الخط الرأسي --}}
                        <div class="report-color-line bg-warning"></div>
                        <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                            <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.sell-payment-report')</h4>
                            <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="عرض تقارير مفصلة عن جميع المبيعات.">
                                <i class="bi bi-question-circle text-muted fs-4"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <a href="{{ route('product-purchase-report') }}" class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                    <div class="card-body d-flex p-0">
                        {{-- الخط الرأسي --}}
                        <div class="report-color-line bg-primary"></div>
                        <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                            <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-purchase-report')</h4>
                            <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="عرض تقارير مفصلة عن جميع المنتجات التي تم شرائها">
                                <i class="bi bi-question-circle text-muted fs-4"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-md-4 col-lg-3 col-xl-2">
                <a href="{{ route('product-sales-report') }}" class="card card-flush h-100 border border-2 border-gray-300 hover-elevate-up shadow-sm">
                    <div class="card-body d-flex p-0">
                        {{-- الخط الرأسي --}}
                        <div class="report-color-line bg-warning"></div>
                        <div class="flex-grow-1 p-5 d-flex align-items-center justify-content-between">
                            <h4 class="fs-6 fw-bolder mb-0 text-dark me-2">@lang('menuItemLang.product-sales-report')</h4>
                            <div class="report-info-icon" data-bs-toggle="tooltip" data-bs-placement="top" title="عرض تقارير مفصلة عن جميع مبيعات المنتجات .">
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