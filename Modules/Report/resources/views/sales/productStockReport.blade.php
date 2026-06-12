@extends('layouts.app')

@section('title', __('menuItemLang.product-inventory'))

@section('css')
<style>
    .pmr-wrap {
        --pmr-radius: 16px;
        --pmr-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        --pmr-border: #eef1f6;
        --pmr-brand: var(--bs-primary);
        --pmr-brand-light: var(--bs-primary-light);
        --pmr-brand-subtle: var(--bs-primary-bg-subtle, #f8efcf);
        --pmr-brand-border: var(--bs-primary-border-subtle, #eed592);
        --pmr-brand-dark: var(--bs-text-primary, #c99a19);
        --pmr-brand-deep: #946f11;
    }

    .pmr-hero {
        background: linear-gradient(135deg, #ffffff 0%, var(--pmr-brand-light) 52%, var(--pmr-brand-subtle) 100%);
        border: 1px solid var(--pmr-brand-border);
        border-radius: var(--pmr-radius);
        padding: 1.5rem 1.75rem;
        box-shadow: var(--pmr-shadow);
        margin-bottom: 1.5rem;
    }

    .pmr-hero-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: #fff;
        color: var(--pmr-brand);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.45rem;
        box-shadow: 0 8px 18px rgba(var(--bs-primary-rgb), .18);
        flex-shrink: 0;
    }

    .pmr-filter-card {
        background: #fff;
        border: 1px solid var(--pmr-border);
        border-radius: var(--pmr-radius);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--pmr-shadow);
    }

    .pmr-filter-label {
        font-size: .8rem;
        font-weight: 600;
        color: #7e8299;
        margin-bottom: .45rem;
        letter-spacing: .01em;
    }

    .pmr-filter-actions {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .pmr-empty {
        border: 1px dashed var(--pmr-brand-border);
        border-radius: var(--pmr-radius);
        background: var(--pmr-brand-light);
        padding: 3rem 1.5rem;
        text-align: center;
    }

    .pmr-empty-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        background: var(--pmr-brand-subtle);
        color: var(--pmr-brand);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
    }

    .pmr-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 1199px) {
        .pmr-metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .pmr-metrics-grid {
            grid-template-columns: 1fr;
        }
    }

    .pmr-metric {
        background: #fff;
        border: 1px solid var(--pmr-border);
        border-radius: 14px;
        padding: 1rem 1.1rem;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease;
        height: 100%;
    }

    .pmr-metric:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
    }

    .pmr-metric-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .65rem;
    }

    .pmr-metric-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .pmr-metric-label {
        font-size: .78rem;
        font-weight: 600;
        color: #a1a5b7;
        line-height: 1.35;
    }

    .pmr-metric-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: #181c32;
        line-height: 1.3;
        word-break: break-word;
    }

    .pmr-metric--neutral .pmr-metric-icon { background: #f5f8fa; color: #7e8299; }
    .pmr-metric--primary .pmr-metric-icon { background: var(--pmr-brand-light); color: var(--pmr-brand); }
    .pmr-metric--primary .pmr-metric-value { color: var(--pmr-brand-dark); }
    .pmr-metric--danger .pmr-metric-icon { background: #fff5f8; color: #f1416c; }
    .pmr-metric--danger .pmr-metric-value { color: #f1416c; }
    .pmr-metric--warning .pmr-metric-icon { background: var(--pmr-brand-subtle); color: var(--pmr-brand-deep); }
    .pmr-metric--warning .pmr-metric-value { color: var(--pmr-brand-deep); }
    .pmr-metric--info .pmr-metric-icon { background: var(--pmr-brand-light); color: var(--pmr-brand-dark); }
    .pmr-metric--info .pmr-metric-value { color: var(--pmr-brand-dark); }
    .pmr-metric--success .pmr-metric-icon { background: var(--pmr-brand-subtle); color: var(--pmr-brand); }
    .pmr-metric--success .pmr-metric-value { color: var(--pmr-brand); }
    .pmr-metric--dark .pmr-metric-icon { background: #f1f1f4; color: #3f4254; }

    .pmr-stock-hero {
        grid-column: 1 / -1;
        background: linear-gradient(135deg, var(--pmr-brand-light) 0%, var(--pmr-brand-subtle) 100%);
        border: 1px solid var(--pmr-brand-border);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        box-shadow: 0 10px 28px rgba(var(--bs-primary-rgb), .14);
    }

    .pmr-stock-hero.is-negative {
        background: linear-gradient(135deg, #fff5f8 0%, #fef2f2 100%);
        border-color: #fecdd3;
        box-shadow: 0 10px 28px rgba(241, 65, 108, .12);
    }

    .pmr-stock-hero-label {
        font-size: .9rem;
        font-weight: 600;
        color: #5e6278;
        margin-bottom: .25rem;
    }

    .pmr-stock-hero-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--pmr-brand-deep);
        line-height: 1.2;
    }

    .pmr-stock-hero.is-negative .pmr-stock-hero-value {
        color: #e11d48;
    }

    .pmr-stock-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .45rem .75rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        background: rgba(255, 255, 255, .8);
        color: var(--pmr-brand-deep);
    }

    .pmr-stock-hero.is-negative .pmr-stock-hero-badge {
        color: #e11d48;
    }

    .pmr-table-card {
        border-radius: var(--pmr-radius);
        border: 1px solid var(--pmr-border);
        box-shadow: var(--pmr-shadow);
        overflow: hidden;
    }

    .pmr-table-filters {
        background: var(--pmr-brand-light);
        border-bottom: 1px solid var(--pmr-brand-border);
        padding: 1rem 1.25rem;
    }

    .pmr-table-filter-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: .75rem;
    }

    .pmr-table-filter-date {
        flex: 0 1 240px;
        min-width: 200px;
    }

    .pmr-table-filter-process {
        flex: 1 1 280px;
        min-width: 220px;
        max-width: 420px;
    }

    .pmr-table-filter-actions {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        gap: .5rem;
        margin-inline-start: auto;
    }

    .pmr-table-filter-process .select2-container {
        width: 100% !important;
    }

    .pmr-table-filter-process .select2-container .select2-selection--multiple {
        min-height: 42px !important;
        max-height: 42px !important;
        overflow: hidden;
    }

    .pmr-table-filter-process .select2-container .select2-selection--multiple .select2-selection__rendered {
        white-space: nowrap !important;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex !important;
        flex-wrap: nowrap;
        padding-top: 6px;
    }

    .pmr-table-filter-process .select2-container .select2-selection--multiple .select2-selection__choice {
        margin-top: 0;
        font-size: 11px;
        padding: 1px 6px;
    }

    .select2-container .select2-selection--multiple {
        height: auto !important;
        min-height: 44px;
    }

    .select2-container .select2-selection--multiple .select2-selection__rendered {
        white-space: normal !important;
    }

    @media (max-width: 767px) {
        .pmr-table-filter-row {
            flex-direction: column;
            align-items: stretch;
        }

        .pmr-table-filter-date,
        .pmr-table-filter-process {
            flex: 1 1 100%;
            max-width: none;
        }

        .pmr-table-filter-actions {
            margin-inline-start: 0;
            width: 100%;
        }
    }

    #kt_ProductSales_table .inv-table-icon-circle {
        transition: transform .15s ease, box-shadow .15s ease;
    }

    #kt_ProductSales_table tbody tr:hover .inv-table-icon-circle {
        transform: scale(1.06);
        box-shadow: 0 6px 14px rgba(15, 23, 42, .12);
    }

    #kt_ProductSales_table td:nth-child(4),
    #kt_ProductSales_table td:nth-child(5),
    #kt_ProductSales_table td:nth-child(8),
    #kt_ProductSales_table th:nth-child(4),
    #kt_ProductSales_table th:nth-child(5),
    #kt_ProductSales_table th:nth-child(8) {
        text-align: center;
        vertical-align: middle;
    }
</style>
@stop

@section('content')
@php
    $stockValue = $metrics['quantity_on_inventory'] ?? '---';
    $isNegativeStock = is_string($stockValue) && str_contains($stockValue, '-');

    $metricCards = [
        ['key' => 'opening_inventory', 'label' => __('report::fields.opening_inventory'), 'icon' => 'bi bi-bookmark-star', 'tone' => 'neutral'],
        ['key' => 'purchased_quantity', 'label' => __('report::fields.purchased_quantity'), 'icon' => 'bi bi-bag-plus', 'tone' => 'primary'],
        ['key' => 'sales_quantity', 'label' => __('report::fields.sales_quantity'), 'icon' => 'bi bi-cart-check', 'tone' => 'danger'],
        ['key' => 'waste', 'label' => __('report::fields.waste'), 'icon' => 'bi bi-trash3', 'tone' => 'warning'],
        ['key' => 'purchase_returns', 'label' => __('report::fields.purchase_returns'), 'icon' => 'bi bi-arrow-return-left', 'tone' => 'info'],
        ['key' => 'transferred_quantity', 'label' => __('report::fields.transferred_quantity'), 'icon' => 'bi bi-arrow-left-right', 'tone' => 'success'],
        ['key' => 'production_quantity', 'label' => __('report::fields.production_quantity'), 'icon' => 'bi bi-gear-wide-connected', 'tone' => 'dark'],
        ['key' => 'counted_quantity', 'label' => __('report::fields.counted_quantity'), 'icon' => 'bi bi-clipboard-check', 'tone' => 'neutral'],
    ];
@endphp

<div class="pmr-wrap">
    <div class="pmr-hero">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <div class="pmr-hero-icon">
                <i class="bi bi-archive"></i>
            </div>
            <div class="flex-grow-1">
                <h2 class="fs-3 fw-bold text-gray-900 mb-1">@lang('menuItemLang.product-inventory')</h2>
                <p class="text-muted fs-7 mb-0">@lang('report::purchase.product_stock_report_details')</p>
            </div>
        </div>
    </div>

    <div class="pmr-filter-card mb-5">
        <form id="mainFilterForm">
            <div class="row g-4 align-items-end">
                <div class="col-lg-4 col-md-6">
                    <div class="pmr-filter-label required">@lang('report::purchase.Branch')</div>
                    <select class="form-select form-select-solid" id="branchFilter" name="establishment_id" data-control="select2" data-placeholder="@lang('report::purchase.Branch')">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="pmr-filter-label required">@lang('report::purchase.Product')</div>
                    <select class="form-select form-select-solid" id="productFilter" name="product_id" data-control="select2" data-placeholder="@lang('report::purchase.Product')">
                        <option value=""></option>
                    </select>
                </div>
                <div class="col-lg-4 col-md-12">
                    <div class="pmr-filter-actions">
                        <button type="button" class="btn btn-primary flex-grow-1" id="applyMainFilter">
                            <i class="bi bi-search me-1"></i> @lang('report::general.Apply Filter')
                        </button>
                        <button type="button" class="btn btn-light border" id="clearMainFilter">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if (! $showReport)
        <div class="pmr-empty">
            <div class="pmr-empty-icon">
                <i class="bi bi-funnel"></i>
            </div>
            <h4 class="fw-bold text-gray-800 mb-2">@lang('report::purchase.select_product_and_branch')</h4>
            <p class="text-muted fs-7 mb-0">@lang('report::purchase.product_stock_report_details')</p>
        </div>
    @else
        <div class="pmr-metrics-grid">
            @foreach ($metricCards as $card)
                <div class="pmr-metric pmr-metric--{{ $card['tone'] }}">
                    <div class="pmr-metric-top">
                        <div class="pmr-metric-label">{{ $card['label'] }}</div>
                        <span class="pmr-metric-icon"><i class="{{ $card['icon'] }}"></i></span>
                    </div>
                    <div class="pmr-metric-value">{{ $metrics[$card['key']] }}</div>
                </div>
            @endforeach

            <div class="pmr-stock-hero {{ $isNegativeStock ? 'is-negative' : '' }}">
                <div>
                    <div class="pmr-stock-hero-label">@lang('report::fields.quantity_on_inventory')</div>
                    <div class="pmr-stock-hero-value">{{ $stockValue }}</div>
                </div>
                <div class="pmr-stock-hero-badge">
                    <i class="bi {{ $isNegativeStock ? 'bi-exclamation-triangle' : 'bi-check-circle' }}"></i>
                    {{ $isNegativeStock ? __('report::purchase.stock_deficit') : __('report::purchase.current_balance') }}
                </div>
            </div>
        </div>

        <div class="card card-flush pmr-table-card">
            <x-cards.card-header class="align-items-center py-5 gap-2 gap-md-5 border-0">
                <div class="card-title">
                    <h3 class="fs-4 fw-bold mb-0">@lang('menuItemLang.product-inventory')</h3>
                </div>
                <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                    <x-tables.table-header model="ProductSales" url="create-purchases-invoice" :addButton="false" module="report">
                        <x-slot:export>
                            <x-tables.export-menu id="purchases" />
                        </x-slot:export>
                    </x-tables.table-header>
                </div>
            </x-cards.card-header>

            <div class="pmr-table-filters">
                <form id="inventoryFilterForm">
                    <div class="pmr-table-filter-row">
                        <div class="pmr-table-filter-date">
                            <div class="pmr-filter-label">@lang('report::purchase.Date Range')</div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-muted"></i></span>
                                <input type="text" class="form-control form-control-solid form-control-sm border-start-0" id="inventoryDateRange" name="inventory_date_range" placeholder="@lang('report::purchase.Date Range')" />
                            </div>
                        </div>
                        <div class="pmr-table-filter-process">
                            <div class="pmr-filter-label">@lang('report::purchase.Process Type')</div>
                            <select class="form-select form-select-solid form-select-sm" id="processTypeFilter" name="process_type[]" data-control="select2" data-placeholder="@lang('report::purchase.All Processes')" multiple>
                                <option></option>
                                <option value="sell">@lang('report::purchase.sell')</option>
                                <option value="purchases">@lang('report::purchase.purchase')</option>
                                <option value="PREP">@lang('report::purchase.prep')</option>
                                <option value="WASTE">@lang('report::purchase.waste')</option>
                                <option value="TRANSFER">@lang('report::purchase.transfer')</option>
                                <option value="purchases-return">@lang('report::purchase.purchases-return')</option>
                                <option value="sell-return">@lang('report::purchase.sell-return')</option>
                            </select>
                        </div>
                        <div class="pmr-table-filter-actions">
                            <button type="button" class="btn btn-sm btn-primary" id="applyFilter">
                                <i class="bi bi-funnel me-1"></i> @lang('report::general.Apply Filter')
                            </button>
                            <button type="button" class="btn btn-sm btn-light border" id="clearFilter" title="@lang('report::general.Remove filter')">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> @lang('report::general.Remove filter')
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <x-cards.card-body class="table-responsive pt-0">
                <x-tables.table :columns=$columns model="ProductSales" module="report" :idColumn="false" :actionColumn="false" />
            </x-cards.card-body>
        </div>
    @endif
</div>

@stop

@section('script')
@parent
<script src="{{ url('js/table.js') }}"></script>
<script src="{{ url('/modules/Sales/js/select-2.js') }}"></script>
<script src="{{ url('/modules/Sales/js/localeSettings.js') }}"></script>
<script src="{{ url('/modules/Sales/js/daterangepicker.js') }}"></script>
<script type="text/javascript" src="/vfs_fonts.js"></script>

<script>
    "use strict";
    let dataTable;
    const table = $('#kt_ProductSales_table');
    const reportBaseUrl = "{{ route('Product-Stock-Report') }}";
    const selectedProductId = "{{ $productId }}";
    const selectedEstablishmentId = "{{ $establishmentId }}";
    const showReport = @json($showReport);

    function getFilterParams() {
        return {
            product_id: selectedProductId,
            establishment_id: selectedEstablishmentId,
            process_type: $('#processTypeFilter').val() || [],
            inventory_date_range: $('#inventoryDateRange').val()
        };
    }

    function populateBranches() {
        $.ajax({
            url: "{{ route('branches') }}",
            type: 'GET',
            success: function(response) {
                if (!response.success) return;
                const branchSelect = $('#branchFilter');
                branchSelect.empty().append(new Option('', '', false, false));
                response.data.forEach(branch => {
                    const option = new Option(branch.name, branch.id, false, branch.id == selectedEstablishmentId);
                    branchSelect.append(option);
                });
                branchSelect.trigger('change');
            }
        });
    }

    function populateProducts() {
        $.ajax({
            url: "{{ route('retrieveProducts') }}",
            type: 'GET',
            success: function(response) {
                if (!response.success) return;
                const productSelect = $('#productFilter');
                productSelect.empty().append(new Option('', '', false, false));
                response.data.forEach(product => {
                    const option = new Option(product.name, product.id, false, product.id == selectedProductId);
                    productSelect.append(option);
                });
                productSelect.trigger('change');
            }
        });
    }

    function initDatatable() {
        dataTable = $(table).DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: reportBaseUrl,
                data: function(d) {
                    Object.assign(d, getFilterParams());
                }
            },
            info: false,
            columns: [
                { data: 'transfer_date', name: 'transfer_date' },
                { data: 'product_name', name: 'product_name' },
                { data: 'establishment_name', name: 'establishment_name' },
                { data: 'transfer_in_out', name: 'transfer_in_out' },
                { data: 'type', name: 'type' },
                { data: 'ref_no', name: 'ref_no' },
                { data: 'quantity', name: 'quantity' },
                { data: 'balance_after', name: 'balance_after' },
                { data: 'entity', name: 'entity' }
            ],
            order: [[0, 'desc']],
            scrollX: true,
            pageLength: 10,
            drawCallback: function() {
                KTMenu.createInstances();
            }
        });
    }

    $(document).ready(function() {
        populateBranches();
        populateProducts();
        $('.form-select').select2();

        $('#applyMainFilter').on('click', function() {
            const productId = $('#productFilter').val();
            const branchId = $('#branchFilter').val();
            if (!productId || !branchId) {
                alert(@json(__('report::purchase.select_product_and_branch')));
                return;
            }
            window.location.href = reportBaseUrl + '?product_id=' + encodeURIComponent(productId) + '&establishment_id=' + encodeURIComponent(branchId);
        });

        $('#clearMainFilter').on('click', function() {
            window.location.href = reportBaseUrl;
        });

        if (!showReport || !table.length) return;

        initDatatable();
        exportButtons([0, 1, 2, 3, 4, 5, 6, 7, 8], '#kt_ProductSales_table');
        handleSearchDatatable();

        $('#inventoryDateRange').daterangepicker({
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: 'YYYY-MM-DD'
            }
        });

        $('#inventoryDateRange').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#inventoryDateRange').on('cancel.daterangepicker', function() {
            $(this).val('');
        });

        $('#applyFilter').on('click', function() {
            dataTable.ajax.reload();
        });

        $('#clearFilter').on('click', function() {
            $('#inventoryFilterForm')[0].reset();
            $('#processTypeFilter').val(null).trigger('change');
            $('#inventoryDateRange').val('');
            dataTable.ajax.reload();
        });
    });
</script>
@endsection
