<?php

/**
 * Dashboard hub vertical tabs (main /dashboard page).
 */
return [
    [
        'id' => 'overview',
        'type' => 'inline',
        'label' => 'menuItemLang.dashboard',
        'icon' => 'fas fa-home',
        'permission' => null,
    ],
    [
        'id' => 'sales',
        'type' => 'embed',
        'label' => 'menuItemLang.sales-dashbord',
        'icon' => 'fas fa-chart-line',
        'route' => 'sales-dashbord',
        'permission' => 'sales.all.show',
    ],
    [
        'id' => 'purchases',
        'type' => 'embed',
        'label' => 'menuItemLang.purchase-dashbord',
        'icon' => 'fas fa-shopping-cart',
        'route' => 'purchase-dashbord',
        'permission' => 'purchases.all.show',
    ],
    [
        'id' => 'products',
        'type' => 'embed',
        'label' => 'menuItemLang.product_dashboard',
        'icon' => 'fas fa-box',
        'route' => 'product.dashboard',
        'permission' => 'products.dashboard.show',
    ],
    [
        'id' => 'inventory',
        'type' => 'embed',
        'label' => 'menuItemLang.inventory_dashboard',
        'icon' => 'fas fa-warehouse',
        'route' => 'inventory.dashboard',
        'permission' => 'inventory.dashboard.show',
    ],
    [
        'id' => 'accounting',
        'type' => 'embed',
        'label' => 'menuItemLang.accounting_dashboard',
        'icon' => 'fas fa-calculator',
        'route' => 'accounting-dashboard',
        'permission' => 'accounting.Dashboard.show',
    ],
];
