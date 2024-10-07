@extends('employee::layouts.master')

@section('title', __('menuItemLang.employees-dashboard'))
@section('content')
    <div class="row g-5 g-xl-8">
        <div class="col-xl-4">
            <!--begin::Statistics Widget 3-->
            <div class="card card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body d-flex flex-column p-0">
                    <div class="d-flex flex-stack flex-grow-1 card-p">
                        <div class="d-flex flex-column me-2">
                            <a href="#" class="text-gray-900 text-hover-primary fw-bold fs-3">Weekly Sales</a>
                            <span class="text-muted fw-semibold mt-1">Your Weekly Sales Chart</span>
                        </div>
                        <span class="symbol symbol-50px">
                            <span class="symbol-label fs-5 fw-bold bg-light-success text-success">+100</span>
                        </span>
                    </div>
                    <div class="statistics-widget-3-chart card-rounded-bottom" data-kt-chart-color="success"
                        style="height: 150px"></div>
                </div>
                <!--end::Body-->
            </div>
            <!--end::Statistics Widget 3-->
        </div>
        <div class="col-xl-4">
            <!--begin::Statistics Widget 3-->
            <div class="card card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body d-flex flex-column p-0">
                    <div class="d-flex flex-stack flex-grow-1 card-p">
                        <div class="d-flex flex-column me-2">
                            <a href="#" class="text-gray-900 text-hover-primary fw-bold fs-3">Authors Progress</a>
                            <span class="text-muted fw-semibold mt-1">Marketplace Authors Chart</span>
                        </div>
                        <span class="symbol symbol-50px">
                            <span class="symbol-label fs-5 fw-bold bg-light-danger text-danger">-260</span>
                        </span>
                    </div>
                    <div class="statistics-widget-3-chart card-rounded-bottom" data-kt-chart-color="danger"
                        style="height: 150px"></div>
                </div>
                <!--end::Body-->
            </div>
            <!--end::Statistics Widget 3-->
        </div>
        <div class="col-xl-4">
            <!--begin::Statistics Widget 3-->
            <div class="card card-xl-stretch mb-5 mb-xl-8">
                <!--begin::Body-->
                <div class="card-body d-flex flex-column p-0">
                    <div class="d-flex flex-stack flex-grow-1 card-p">
                        <div class="d-flex flex-column me-2">
                            <a href="#" class="text-gray-900 text-hover-primary fw-bold fs-3">Sales Progress</a>
                            <span class="text-muted fw-semibold mt-1">Marketplace Sales Chart</span>
                        </div>
                        <span class="symbol symbol-50px">
                            <span class="symbol-label fs-5 fw-bold bg-light-primary text-primary">+180</span>
                        </span>
                    </div>
                    <div class="statistics-widget-3-chart card-rounded-bottom" data-kt-chart-color="primary"
                        style="height: 150px"></div>
                </div>
                <!--end::Body-->
            </div>
            <!--end::Statistics Widget 3-->
        </div>
    </div>
    
    <div class="row g-5 g-xl-8">
        <div class="col-xl-4">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-danger hoverable card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-basket text-white fs-2x ms-n1"></i>
                    <div class="text-white fw-bold fs-2 mb-2 mt-5">Shopping Cart</div>
                    <div class="fw-semibold text-white">Lands, Houses, Ranchos, Farms</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
        <div class="col-xl-4">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-primary hoverable card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-cheque text-white fs-2x ms-n1"></i>
                    <div class="text-white fw-bold fs-2 mb-2 mt-5">Appartments</div>
                    <div class="fw-semibold text-white">Flats, Shared Rooms, Duplex</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
        <div class="col-xl-4">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-success hoverable card-xl-stretch mb-5 mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-chart-simple-3 text-white fs-2x ms-n1"></i>
                    <div class="text-white fw-bold fs-2 mb-2 mt-5">Sales Stats</div>
                    <div class="fw-semibold text-white">50% Increased for FY20</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
    </div>
    <div class="row g-5 g-xl-8">
        <div class="col-xl-3">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-body hoverable card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-chart-simple text-primary fs-2x ms-n1"></i>
                    <div class="text-gray-900 fw-bold fs-2 mb-2 mt-5">500M$</div>
                    <div class="fw-semibold text-gray-400">SAP UI Progress</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
        <div class="col-xl-3">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-dark hoverable card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-cheque text-gray-100 fs-2x ms-n1"></i>
                    <div class="text-gray-100 fw-bold fs-2 mb-2 mt-5">+3000</div>
                    <div class="fw-semibold text-gray-100">New Customers</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
        <div class="col-xl-3">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-warning hoverable card-xl-stretch mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-briefcase text-white fs-2x ms-n1"></i>
                    <div class="text-white fw-bold fs-2 mb-2 mt-5">$50,000</div>
                    <div class="fw-semibold text-white">Milestone Reached</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
        <div class="col-xl-3">
            <!--begin::Statistics Widget 5-->
            <a href="#" class="card bg-info hoverable card-xl-stretch mb-5 mb-xl-8">
                <!--begin::Body-->
                <div class="card-body">
                    <i class="ki-outline ki-chart-pie-simple text-white fs-2x ms-n1"></i>
                    <div class="text-white fw-bold fs-2 mb-2 mt-5">$50,000</div>
                    <div class="fw-semibold text-white">Milestone Reached</div>
                </div>
                <!--end::Body-->
            </a>
            <!--end::Statistics Widget 5-->
        </div>
    </div>
@endsection
