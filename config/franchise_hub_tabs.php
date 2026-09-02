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
        'permission' => \Modules\Franchise\Support\FranchisePermissions::for('Companies', 'show'),
    ],
    [
        'id' => 'branches',
        'label' => 'menuItemLang.branches_mgmt',
        'icon' => 'fas fa-code-branch',
        'route' => 'franchise.branches.index',
        'permission' => \Modules\Franchise\Support\FranchisePermissions::for('Branches', 'show'),
    ],
    [
        'id' => 'products',
        'label' => 'menuItemLang.franchise_product_mgmt',
        'icon' => 'fas fa-boxes-stacked',
        'route' => 'franchise.products.index',
        'permission' => \Modules\Franchise\Support\FranchisePermissions::for('Products', 'show'),
    ],
    [
        'id' => 'menus',
        'label' => 'menuItemLang.custom_menus_mgmt',
        'icon' => 'fas fa-list',
        'route' => 'franchise.franchise.custom_menus.index',
        'permission' => \Modules\Franchise\Support\FranchisePermissions::for('Menus', 'show'),
    ],
];
