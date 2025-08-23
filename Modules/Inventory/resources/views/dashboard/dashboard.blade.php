@extends('layouts.app')
@section('title', __('menuItemLang.inventory_dashboard'))

@section('css')
<style>
    :root {
        --primary-color: #4361ee;
        --secondary-color: #4cc9f0;
        --success-color: #0abb87;
        --danger-color: #ff5b5b;
        --warning-color: #f8961e;
        --info-color: #3a86ff;
        --purple-color: #7209b7;
        --indigo-color: #480ca8;
        --text-dark: #212529;
        --text-muted: #6c757d;
        --bg-light: #f5f7fa;
        --card-bg: #ffffff;
        --border-color: #e9ecef;
    }

    body {
        background-color: var(--bg-light);
        color: var(--text-dark);
        font-family: 'Tajawal', sans-serif;
        direction: rtl;
        text-align: right;
        overflow-x: hidden;
    }

    .container-fluid {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }

    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94), box-shadow 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .summary-card-v2 {
        background-color: var(--card-bg);
        border-radius: 16px;
        padding: 30px;
        display: flex;
        align-items: center;
        gap: 25px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .summary-card-v2 .icon-wrapper {
        width: 70px;
        height: 70px;
        min-width: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 2rem;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .summary-card-v2 .content {
        flex-grow: 1;
    }

    .summary-card-v2 .title {
        font-size: 1.1rem;
        font-weight: 500;
        color: var(--text-muted);
        margin-bottom: 8px;
    }

    .summary-card-v2 .value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1;
        color: var(--text-dark);
    }

    .bg-primary-v2 {
        background-color: var(--primary-color);
    }

    .bg-info-v2 {
        background-color: var(--info-color);
    }

    .bg-success-v2 {
        background-color: var(--success-color);
    }

    .bg-danger-v2 {
        background-color: var(--danger-color);
    }

    .quick-actions-container {
        padding: 40px;
        background-color: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        margin-bottom: 3rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }

    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: white;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        padding: 20px;
        border-radius: 12px;
        min-width: 160px;
        height: 120px;
        position: relative;
        overflow: hidden;
        font-size: 1rem;
        font-weight: 600;
        background: linear-gradient(135deg, var(--primary-color), var(--indigo-color));
    }

    .quick-action-btn:hover {
        transform: translateY(-6px) scale(1.03);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
    }

    .bg-info-gradient {
        background: linear-gradient(135deg, var(--info-color), #4895ef);
    }

    .bg-danger-gradient {
        background: linear-gradient(135deg, var(--danger-color), #e63946);
    }

    .bg-success-gradient {
        background: linear-gradient(135deg, var(--success-color), #09b47e);
    }

    .bg-primary-gradient {
        background: linear-gradient(135deg, var(--primary-color), #480ca8);
    }

    .quick-action-btn .btn-icon {
        font-size: 2.2rem;
        margin-bottom: 12px;
        opacity: 0.9;
    }

    /* New and improved Inventory Stats Style */
    .inventory-stats-list {
        display: flex;
        flex-wrap: wrap;
        /* New: Allows items to wrap on smaller screens */
        justify-content: space-between;
        /* New: Spaces items out */
        gap: 1rem;
        padding: 0;
        list-style: none;
        margin: 0;
    }

    .inventory-item {
        display: flex;
        flex-direction: column;
        /* New: changed to column for side-by-side layout */
        flex-basis: 48%;
        /* New: makes items sit next to each other */
        align-items: flex-start;
        gap: 15px;
        padding: 1.25rem;
        border-radius: 12px;
        background-color: var(--bg-light);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
        position: relative;
    }

    .inventory-item:hover {
        transform: translateY(-3px);
        background-color: #eef1f5;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    .inventory-item::before {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 6px;
        border-radius: 0 12px 12px 0;
        background-color: transparent;
        transition: background-color 0.3s ease;
    }

    .inventory-item.highest-item::before {
        background-color: var(--success-color);
    }

    .inventory-item.lowest-item::before {
        background-color: var(--danger-color);
    }

    .inventory-item .icon {
        font-size: 1.8rem;
        min-width: 40px;
        text-align: center;
        margin-bottom: 0.5rem;
        /* New: added margin to separate icon from text */
    }

    .inventory-item.highest-item .icon {
        color: var(--success-color);
    }

    .inventory-item.lowest-item .icon {
        color: var(--danger-color);
    }

    .inventory-item .details {
        flex-grow: 1;
        text-align: right;
    }

    .inventory-item .details h6 {
        margin-bottom: 0.25rem;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .inventory-item .details .quantity {
        font-size: 0.95rem;
        color: var(--text-muted);
    }

    .card-body h5.card-title {
        font-weight: 700;
        font-size: 1.5rem;
    }

    @media (max-width: 767px) {
        .inventory-item {
            flex-basis: 100%;
            /* On small screens, items take up full width */
        }
    }
</style>
@endsection

@section('content')

<div class="container-fluid py-4">
    <!-- Row to contain the cards -->
    <div class="row g-4 mb-5">

        <!-- Card for Warehouses -->
        <div class="col-md-4 col-lg-3">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-primary-v2">
                    <i class="fas fa-warehouse"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('inventory::dashboard.warehouses_count')</h6>
                    <h3 class="value text-primary">{{ $warehousesCount ?? 0  }}</h3>
                </div>
            </div>
        </div>

        <!-- Card for Transfers -->
        <div class="col-md-4 col-lg-3">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-primary-v2">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('inventory::dashboard.transfers_count')</h6>
                    <h3 class="value text-primary">{{ $transferCount ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <!-- Card for Preparations -->
        <div class="col-md-4 col-lg-3">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-primary-v2">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('inventory::dashboard.preparations_count')</h6>
                    <h3 class="value text-primary">{{ $prepCount ?? 0 }}</h3>
                </div>
            </div>
        </div>

        <!-- Card for Waste -->
        <div class="col-md-4 col-lg-3">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-primary-v2">
                    <i class="fas fa-dumpster"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('inventory::dashboard.waste_count')</h6>
                    <h3 class="value text-primary">{{ $wasteCount ?? 0 }}</h3>
                </div>
            </div>
        </div>

    </div>
</div>
<div class="row g-4">
    <div class="col-12">
        <div class="quick-actions-container d-flex flex-wrap justify-content-center">
            <a href="productInventory" class="quick-action-btn bg-info-gradient">
                <div class="icon-wrapper">
                    <i class="fas fa-box btn-icon"></i>
                </div>
                <span class="btn-label">@lang('inventory::dashboard.view_products')</span>
            </a>
            <a href="waste" class="quick-action-btn bg-danger-gradient">
                <div class="icon-wrapper">
                    <i class="fas fa-exclamation-circle btn-icon"></i>
                </div>
                <span class="btn-label">@lang('inventory::dashboard.damaged_products')</span>
            </a>
            <a href="transfer" class="quick-action-btn bg-primary-gradient">
                <div class="icon-wrapper">
                    <i class="fas fa-exchange-alt btn-icon"></i>
                </div>
                <span class="btn-label">@lang('inventory::dashboard.transfer_product')</span>
            </a>
            <a href="prep" class="quick-action-btn bg-success-gradient">
                <div class="icon-wrapper">
                    <i class="fas fa-mortar-pestle btn-icon"></i>
                </div>
                <span class="btn-label">@lang('inventory::dashboard.prepare_recipe')</span>
            </a>
        </div>
    </div>
</div>
<div class="row g-4 mb-5">
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header bg-transparent py-4">
                <h5 class="card-title mb-0">@lang('inventory::dashboard.inventory_overview')</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    @forelse($warehouses as $warehouse)
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title text-center text-primary mb-4">{{ $warehouse->name }}</h5>

                                <div class="inventory-stats-list">
                                    <div class="inventory-item highest-item">
                                        <div class="icon">
                                            <i class="fas fa-arrow-up"></i>
                                        </div>
                                        <div class="details">
                                            @if($warehouse->mostStockedProduct)
                                            <h6 class="text-success">@lang('inventory::dashboard.highest_quantity')</h6>
                                            <div class="product-name">
                                                <strong>{{ $warehouse->mostStockedProduct->name_ar }}</strong>
                                            </div>
                                            <div class="quantity">
                                                @lang('inventory::dashboard.quantity'): {{ $warehouse->mostStockedQuantity }}
                                            </div>
                                            @else
                                            <p class="text-muted mb-0">@lang('inventory::dashboard.no_products')</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="inventory-item lowest-item">
                                        <div class="icon">
                                            <i class="fas fa-arrow-down"></i>
                                        </div>
                                        <div class="details">
                                            @if($warehouse->leastStockedProduct)
                                            <h6 class="text-danger">@lang('inventory::dashboard.lowest_quantity')</h6>
                                            <div class="product-name">
                                                <strong>{{ $warehouse->leastStockedProduct->name_ar }}</strong>
                                            </div>
                                            <div class="quantity">
                                                @lang('inventory::dashboard.quantity'): {{ $warehouse->leastStockedQuantity }}
                                            </div>
                                            @else
                                            <p class="text-muted mb-0">@lang('inventory::dashboard.no_products')</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            @lang('inventory::dashboard.no_warehouses')
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@section('script')
@endsection