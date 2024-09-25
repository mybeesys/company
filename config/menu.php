<?php
return [
    [
        'name' => 'Dashboard',
        'url' => '/',
        'icon' => 'fas fa-home',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'User_module',
        'url' => '/',
        'icon' => 'fas fa-user',
        'permission' => '' ,
        'subMenu' => [
            [
                'name' => 'Users',
                'url' => '/',
                'permission' => '' ,
            ],
            [
                'name' => 'Roles',
                'url' => '/',
                'permission' => '' ,
            ]
        ]
    ],
    [
        'name' => 'Product_module',
        'url' => 'category.index',
        'icon' => 'fas fa-shopping-cart',
        'permission' => '' ,
        'subMenu' => [
            [
                'name' => 'Products',
                'url' => 'category.index',
                'permission' => '' ,
            ],
            [
                'name' => 'Inventory',
                'url' => '/',
                'permission' => '' ,
            ]
            ]
    ],
    [
        'name' => 'accounting_module',
        'url' => '/',
        'icon' => 'fas fa-calculator',
        'permission' => '' ,
        'subMenu' =>[
            [
                'name' => 'chart_of_accounts',
                'url' => '/',
                'permission' => '' ,
            ],
        ]
    ],
    [
        'name' => 'Establishment',
        'url' => '/',
        'icon' => 'fas fa-building',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'CRM',
        'url' => '/',
        'icon' => 'fas fa-cogs',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'Employees',
        'url' => '/',
        'icon' => 'fas fa-id-card',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'Reports',
        'url' => '/',
        'icon' => 'fas fa-bar-chart',
        'permission' => '' ,
        'subMenu' =>[]
    ],
];