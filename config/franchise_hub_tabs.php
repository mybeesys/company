<?php

/**
 * Franchise management hub tabs (/franchise/companies).
 */
return [
    [
        'id' => 'companies',
        'label' => 'menuItemLang.franchise_com',
        'icon' => 'fas fa-building',
        'route' => 'franchise.companies.index',
        'permission' => 'Franchise Companies.all.show',
    ],
    [
        'id' => 'branches',
        'label' => 'menuItemLang.branches_mgmt',
        'icon' => 'fas fa-code-branch',
        'route' => 'franchise.branches.index',
        'permission' => 'Franchise Companies.all.show',
    ],
    [
        'id' => 'products',
        'label' => 'menuItemLang.franchise_product_mgmt',
        'icon' => 'fas fa-boxes-stacked',
        'route' => 'franchise.products.index',
        'permission' => 'Franchise Companies.all.show',
    ],
    [
        'id' => 'menus',
        'label' => 'menuItemLang.custom_menus_mgmt',
        'icon' => 'fas fa-list',
        'route' => 'franchise.franchise.custom_menus.index',
        'permission' => 'Franchise Companies.all.show',
    ],
];
