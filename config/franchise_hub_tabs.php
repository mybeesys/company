<?php

/**
 * Franchise management hub tabs (/franchise/companies).
 */
return [
    [
        'id' => 'companies',
        'type' => 'inline',
        'label' => 'menuItemLang.franchise_com',
        'icon' => 'fas fa-building',
        'permission' => 'Franchise Companies.all.show',
    ],
    [
        'id' => 'branches',
        'type' => 'embed',
        'label' => 'menuItemLang.branches_mgmt',
        'icon' => 'fas fa-code-branch',
        'route' => 'franchise.branches.index',
        'permission' => 'Franchise Companies.all.show',
    ],
    [
        'id' => 'products',
        'type' => 'embed',
        'label' => 'menuItemLang.franchise_product_mgmt',
        'icon' => 'fas fa-boxes-stacked',
        'route' => 'franchise.products.index',
        'permission' => 'Franchise Companies.all.show',
    ],
    [
        'id' => 'menus',
        'type' => 'embed',
        'label' => 'menuItemLang.custom_menus_mgmt',
        'icon' => 'fas fa-list',
        'route' => 'franchise.franchise.custom_menus.index',
        'permission' => 'Franchise Companies.all.show',
    ],
];
