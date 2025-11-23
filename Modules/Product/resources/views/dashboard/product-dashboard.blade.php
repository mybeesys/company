@extends('layouts.app')
@section('title', __('menuItemLang.product_dashboard'))

@section('css')

{{-- <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js" rel="stylesheet"> --}}
<style>
    :root {
        --primary-color: #a7b7ff;
        --secondary-color: #4cc9f0;
        --success-color: #92ffde;
        --danger-color: #ff9595;
        --warning-color: #ffc278;
        --info-color: #7caeff;
        --purple-color: #d493ff;
        --indigo-color: #be95ff;
        --text-dark: #212529;
        --text-muted: #6c757d;
        --bg-light: #f8f9fa;
        --card-bg: #ffffff;
        --border-color: #e9ecef;
    }

    body {
        background-color: var(--bg-light);
        color: var(--text-dark);
        /* font-family: 'Tajawal', sans-serif; */
        direction: rtl;
        text-align: right;
    }

    .container-fluid {
        padding-top: 2rem;
        padding-bottom: 2rem;
    }

    .card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    }

    .summary-card-v2 {
        background-color: var(--card-bg);
        border-radius: 12px;
        padding: 25px;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .summary-card-v2 .icon-wrapper {
        width: 60px;
        height: 60px;
        min-width: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .summary-card-v2 .content {
        flex-grow: 1;
    }

    .summary-card-v2 .title {
        font-size: 1rem;
        font-weight: 500;
        color: var(--text-muted);
        margin-bottom: 5px;
    }

    .summary-card-v2 .value {
        font-size: 2rem;
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

    .bg-purple-v2 {
        background-color: var(--purple-color);
    }

    .bg-warning-v2 {
        background-color: var(--warning-color);
    }

    .bg-indigo-v2 {
        background-color: var(--indigo-color);
    }

    .bg-danger-v2 {
        background-color: var(--danger-color);
    }

    .bg-secondary-v2 {
        background-color: var(--secondary-color);
    }

    .text-primary {
        color: var(--primary-color) !important;
    }

    .text-info {
        color: var(--info-color) !important;
    }

    .text-success {
        color: var(--success-color) !important;
    }

    .text-purple {
        color: var(--purple-color) !important;
    }

    .text-warning {
        color: var(--warning-color) !important;
    }

    .text-indigo {
        color: var(--indigo-color) !important;
    }

    .text-danger {
        color: var(--danger-color) !important;
    }

    .text-secondary {
        color: var(--secondary-color) !important;
    }


    .quick-actions-container {
        padding: 30px;
        background-color: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
    }

    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: white;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        padding: 15px;
        border-radius: 10px;
        width: 150px;
        height: 100px;
        position: relative;
        overflow: hidden;
        margin: 5px;
        font-size: 0.9rem;
    }

    .quick-action-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .quick-action-btn .btn-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
        z-index: 2;
        color: white;
    }

    .quick-action-btn .btn-label {
        font-weight: 600;
        font-size: 0.85rem;
        text-align: center;
        z-index: 2;
    }

    .hover-effect {
        position: absolute;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        top: -50px;
        right: -50px;
        transition: all 0.5s ease;
        z-index: 1;
    }

    .quick-action-btn:hover .hover-effect {
        transform: scale(3);
    }

    .bg-primary {
        background: linear-gradient(135deg, #9caeff, #b393ff);
    }

    .bg-success {
        background: linear-gradient(135deg, #4cc9f0, #4895ef);
    }

    .bg-info {
        background: linear-gradient(135deg, #3a86ff, #4361ee);
    }

    .bg-purple {
        background: linear-gradient(135deg, #7209b7, #560bad);
    }

    .bg-indigo {
        background: linear-gradient(135deg, #480ca8, #3a0ca3);
    }

    .bg-warning {
        background: linear-gradient(135deg, #f8961e, #f3722c);
    }

    .bg-danger {
        background: linear-gradient(135deg, #ffa6a6, #ff969e);
    }

  .bg-primary {
                background: linear-gradient(135deg, #decce2, #decce2);
            }

            .bg-success {
                background: linear-gradient(135deg, #c8eddc, #6acb9e);
            }

            .bg-info {
                background: linear-gradient(135deg, #cef3fb, #76c9db);
            }

            .bg-purple {
                background: linear-gradient(135deg, #fff8cc, #e1d277);
            }

            .bg-indigo {
                background: linear-gradient(135deg, #dfd2ff, #dfd2ff);
            }

            .bg-warning {
                background: linear-gradient(135deg, #f7ccdd, #f7ccdd);
            }
    .bg-danger-light {
        background-color: #fff8f5;
        border-right: 4px solid var(--danger-color);
    }

    .bg-primary-light {
        background-color: #f0f7ff;
        border-right: 4px solid var(--primary-color);
    }

    .bg-purple-light {
        background-color: #f8f5ff;
        border-right: 4px solid var(--purple-color);
    }

    .bg-success-light {
        background-color: #f1faf6;
        border-right: 4px solid var(--success-color);
    }

    .bg-warning-light {
        background-color: #fffaf3;
        border-right: 4px solid var(--warning-color);
    }

    .bg-info-light {
        background-color: #f0faff;
        border-right: 4px solid var(--info-color);
    }

    .bg-indigo-light {
        background-color: #f2f0ff;
        border-right: 4px solid var(--indigo-color);
    }


    .table th,
    .table td {
        white-space: nowrap;
        vertical-align: middle;
        padding: 1rem;
    }

    .table th {
        color: var(--text-muted);
        font-weight: 600;
        border-bottom-width: 2px;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.03);
    }

    .status-badge {
        font-size: 0.8em;
        padding: 0.5em 1em;
        border-radius: 50px;
        font-weight: bold;
    }
</style>
@endsection

@section('content')

<div class="container-fluid py-4">
    <div class="row g-4 mb-5">
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-primary-v2">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_products')</h6>
                    <h3 class="value text-primary">{{ $productsCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-info-v2">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_ingredients')</h6>
                    <h3 class="value text-info">{{ $ingredintsCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-success-v2">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_modifiers')</h6>
                    <h3 class="value text-success">{{ $modifiersCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-secondary-v2">
                    <i class="fas fa-copy"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_variants')</h6>
                    <h3 class="value text-secondary">{{ $variantsCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-purple-v2">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_custom_menus')</h6>
                    <h3 class="value text-purple">{{ $servicesCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-warning-v2">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_service_fees')</h6>
                    <h3 class="value text-warning">{{ $serviceFeesCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-indigo-v2">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_service_types')</h6>
                    <h3 class="value text-indigo">{{ $serviceTypesCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-danger-v2">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_discounts')</h6>
                    <h3 class="value text-danger">{{ $discountsCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-lg-4">
            <div class="summary-card-v2">
                <div class="icon-wrapper bg-primary-v2">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="content">
                    <h6 class="title">@lang('product::dashboard.total_pricings')</h6>
                    <h3 class="value text-primary">{{ $pricingsCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="quick-actions-container d-flex flex-wrap justify-content-center">
                <a href="product/create" class="quick-action-btn bg-primary">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_product')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="ingredient/create" class="quick-action-btn bg-success">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_ingredient')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="modifier/create" class="quick-action-btn bg-warning">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_modifier')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="attribute" class="quick-action-btn bg-info">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_variant')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="customMenu/create" class="quick-action-btn bg-purple">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_custom_menu')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="serviceFee/create" class="quick-action-btn bg-indigo">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_service_fee')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="type-service-create" class="quick-action-btn bg-success">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_service_type')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="discount/create" class="quick-action-btn bg-danger">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-icon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_discount')</span>
                    <div class="hover-effect"></div>
                </a>
                <a href="priceTier" class="quick-action-btn bg-primary">
                    <div class="icon-wrapper">
                        <i class="fas fa-plus-square btn-dicon"></i>
                    </div>
                    <span class="btn-label">@lang('product::dashboard.add_pricing')</span>
                    <div class="hover-effect"></div>
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="card h-100">
            <div class="card-header bg-transparent py-4">
                <h5 class="card-title mb-0">@lang('product::dashboard.latest_products')</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">@lang('product::dashboard.product_name_ar')</th>
                                <th scope="col">@lang('product::dashboard.product_name_en')</th>
                                <th scope="col">@lang('product::dashboard.price')</th>
                                <th scope="col">@lang('product::dashboard.date_added')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($latestProducts as $product)
                            <tr>
                                <td>{{ $product->name_ar ?? 'N/A' }}</td>
                                <td>{{ $product->name_en ?? 'N/A' }}</td>
                                <td>{{ $product->price_with_tax ?? 'N/A' }}</td>
                                <td>{{ \Carbon\Carbon::parse($product->created_at)->format('Y-m-d') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">@lang('product::dashboard.no_products')</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('script')
{{-- <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.js"></script> --}}

@endsection
