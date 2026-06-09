@extends('layouts.app')

@section('title', __('menuItemLang.reports_module'))

@section('content')
    @php
        $reportSections = [
            [
                'title' => __('report::purchase.sell_reports'),
                'tone' => 'warning',
                'icon' => 'bi bi-graph-up-arrow',
                'items' => [
                    [
                        'title' => __('menuItemLang.sell-payment-report'),
                        'route' => route('sell-payment-report'),
                        'hint' => __('report::purchase.sell_payment_report_details'),
                        'icon' => 'bi bi-cash-coin',
                    ],
                    [
                        'title' => __('menuItemLang.product-sales-report'),
                        'route' => route('product-sales-report'),
                        'hint' => __('report::purchase.product_sales_report_details'),
                        'icon' => 'bi bi-basket2-fill',
                    ],
                    [
                        'title' => __('menuItemLang.sales-comparison-report'),
                        'route' => route('sales-comparison-report'),
                        'hint' => __('report::general.sales_comparison_hub_card_hint'),
                        'icon' => 'bi bi-bar-chart-steps',
                    ],
                    [
                        'title' => __('menuItemLang.weekday-sales-report'),
                        'route' => route('weekday-sales-report'),
                        'hint' => __('report::general.weekday_sales_hub_card_hint'),
                        'icon' => 'bi bi-calendar-week',
                    ],
                ],
            ],
            [
                'title' => __('report::purchase.purchase_reports'),
                'tone' => 'primary',
                'icon' => 'bi bi-cart-check',
                'items' => [
                    [
                        'title' => __('menuItemLang.purchase-payment-report'),
                        'route' => route('purchase-payment-report'),
                        'hint' => __('report::purchase.purchase_payment_report_details'),
                        'icon' => 'bi bi-wallet2',
                    ],
                    [
                        'title' => __('menuItemLang.product-purchase-report'),
                        'route' => route('product-purchase-report'),
                        'hint' => __('report::purchase.product_purchase_report_details'),
                        'icon' => 'bi bi-box-seam',
                    ],
                ],
            ],
            [
                'title' => __('report::purchase.inventory_reports'),
                'tone' => 'success',
                'icon' => 'bi bi-boxes',
                'items' => [
                    [
                        'title' => __('menuItemLang.product-inventory-report'),
                        'route' => route('product-inventory-report'),
                        'hint' => __('report::purchase.product_inventory_report_details'),
                        'icon' => 'bi bi-clipboard-data',
                    ],
                    [
                        'title' => __('menuItemLang.product-inventory-summary'),
                        'route' => route('product-inventory-summary'),
                        'hint' => __('report::purchase.product-inventory-summary_details'),
                        'icon' => 'bi bi-bar-chart-line',
                    ],
                    [
                        'title' => __('menuItemLang.product-movement-report'),
                        'route' => route('product-movement-report'),
                        'hint' => __('report::purchase.product_movement_report_details'),
                        'icon' => 'bi bi-arrow-left-right',
                    ],
                    [
                        'title' => __('menuItemLang.product-inventory'),
                        'route' => route('Product-Stock-Report'),
                        'hint' => __('report::purchase.product-inventory-summary_details'),
                        'icon' => 'bi bi-archive',
                    ],
                ],
            ],
            [
                'title' => __('report::purchase.others_reports'),
                'tone' => 'info',
                'icon' => 'bi bi-file-earmark-richtext',
                'items' => [
                    [
                        'title' => __('menuItemLang.Profit-Loss'),
                        'route' => route('Profit-Loss'),
                        'hint' => __('report::purchase.profit-loss_details'),
                        'icon' => 'bi bi-activity',
                    ],
                    [
                        'title' => __('menuItemLang.purchase-sell'),
                        'route' => route('purchase-sell'),
                        'hint' => __('report::purchase.purchase_sell_details'),
                        'icon' => 'bi bi-arrow-left-right',
                    ],
                    [
                        'title' => __('report::fields.register_report'),
                        'route' => url('Register-Report'),
                        'hint' => __('report::fields.register_report_details'),
                        'icon' => 'bi bi-receipt-cutoff',
                    ],
                ],
            ],
        ];
        $totalReports = collect($reportSections)->sum(fn($section) => count($section['items']));
    @endphp

    <div class="card card-flush h-md-100 report-hub-wrap">
        <div class="card-header border-0 pb-2">
            <div class="card-title flex-column align-items-start">
                <h2 class="mb-1">@lang('menuItemLang.reports_module')</h2>
                <span class="text-muted fs-7">@lang('menuItemLang.reports_list')</span>
            </div>
            <div class="card-toolbar">
                <span class="badge badge-light-primary fs-7 fw-bold">{{ $totalReports }} {{ __('messages.reports') ?? 'Reports' }}</span>
            </div>
        </div>
        <div class="card-body pt-2">
            <div class="row g-4 mb-7">
                @foreach ($reportSections as $section)
                    <div class="col-sm-6 col-lg-3">
                        <div class="report-kpi-card report-kpi-{{ $section['tone'] }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fs-7 fw-semibold text-muted">{{ $section['title'] }}</div>
                                    <div class="fs-3 fw-bolder mt-1">{{ count($section['items']) }}</div>
                                </div>
                                <div class="report-kpi-icon"><i class="{{ $section['icon'] }}"></i></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach ($reportSections as $section)
                <div class="report-section mb-8">
                    <div class="d-flex align-items-center mb-4">
                        <div class="report-section-dot bg-{{ $section['tone'] }}"></div>
                        <h3 class="mb-0 ms-2 fs-4 fw-bold">{{ $section['title'] }}</h3>
                    </div>
                    <div class="row g-4">
                        @foreach ($section['items'] as $item)
                            <div class="col-sm-6 col-lg-4 col-xl-3">
                                <a href="{{ $item['route'] }}" class="report-link-card report-link-{{ $section['tone'] }}" data-report-key="{{ md5($item['route']) }}">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="me-2">
                                            <h4 class="fs-6 fw-bolder text-dark mb-1">{{ $item['title'] }}</h4>
                                            <div class="text-muted fs-8">{{ $item['hint'] }}</div>
                                        </div>
                                        <div class="report-link-icon">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </div>
                                    </div>
                                    <div class="report-last-used text-muted fs-8 mt-3">
                                        @lang('messages.last_used'): <span class="report-last-used-value">@lang('messages.never_used')</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .report-hub-wrap {
            border-radius: 1rem;
        }

        .report-kpi-card {
            background: #fff;
            border: 1px solid #eef1f5;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .report-kpi-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .report-kpi-warning .report-kpi-icon {
            background: #fff8dd;
            color: #f6b100;
        }

        .report-kpi-primary .report-kpi-icon {
            background: #eef6ff;
            color: #0095e8;
        }

        .report-kpi-success .report-kpi-icon {
            background: #e8fff3;
            color: #50cd89;
        }

        .report-kpi-info .report-kpi-icon {
            background: #f1faff;
            color: #7239ea;
        }

        .report-section-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            display: inline-block;
        }

        .report-link-card {
            display: block;
            border-radius: 14px;
            border: 1px solid #eef1f5;
            background: #fff;
            padding: 1rem;
            text-decoration: none;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            min-height: 122px;
        }

        .report-link-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 26px rgba(15, 23, 42, .09);
        }

        .report-link-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .report-link-warning .report-link-icon {
            background: #fff8dd;
            color: #f6b100;
        }

        .report-link-primary .report-link-icon {
            background: #eef6ff;
            color: #0095e8;
        }

        .report-link-success .report-link-icon {
            background: #e8fff3;
            color: #50cd89;
        }

        .report-link-info .report-link-icon {
            background: #f1faff;
            color: #7239ea;
        }

        .report-last-used {
            border-top: 1px dashed #e9edf3;
            padding-top: .55rem;
        }
    </style>
@endsection

@section('script')
    @parent
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const storageKey = 'report_hub_last_used';
            const isAr = document.documentElement.lang === 'ar';
            const now = Date.now();

            function relativeText(ts) {
                const diffMin = Math.floor((now - ts) / 60000);
                if (diffMin < 1) return isAr ? 'الآن' : 'just now';
                if (diffMin < 60) return isAr ? `منذ ${diffMin} دقيقة` : `${diffMin} min ago`;
                const diffHour = Math.floor(diffMin / 60);
                if (diffHour < 24) return isAr ? `منذ ${diffHour} ساعة` : `${diffHour} h ago`;
                const diffDay = Math.floor(diffHour / 24);
                return isAr ? `منذ ${diffDay} يوم` : `${diffDay} d ago`;
            }

            let usage = {};
            try {
                usage = JSON.parse(localStorage.getItem(storageKey) || '{}') || {};
            } catch (e) {
                usage = {};
            }

            document.querySelectorAll('.report-link-card[data-report-key]').forEach((card) => {
                const key = card.getAttribute('data-report-key');
                const valueEl = card.querySelector('.report-last-used-value');
                if (valueEl && usage[key]) {
                    valueEl.textContent = relativeText(usage[key]);
                }

                card.addEventListener('click', function() {
                    usage[key] = Date.now();
                    localStorage.setItem(storageKey, JSON.stringify(usage));
                });
            });
        });
    </script>
@endsection
