<?php
return [
    [
        'name' => 'Dashboard',
        'url' => 'dashboard',
        'icon' => 'fas fa-home',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'User_module',
        'url' => 'dashboard',
        'icon' => 'fas fa-user',
        'permission' => '' ,
        'subMenu' => [
            [
                'name' => 'Users',
                'url' => 'dashboard',
                'permission' => '' ,
            ],
            [
                'name' => 'Roles',
                'url' => 'dashboard',
                'permission' => '' ,
            ]
        ]
    ],
    [
        'name' => 'Product_module',
        'url' => 'dashboard',
        'icon' => 'fas fa-shopping-cart',
        'permission' => '' ,
        'subMenu' => [
            [
                'name' => 'Products',
                'url' => 'dashboard',
                'permission' => '' ,
            ],
            [
                'name' => 'Inventory',
                'url' => 'dashboard',
                'permission' => '' ,
            ]
            ]
    ],
    [
        'name' => 'accounting_module',
        'url' => 'dashboard',
        'icon' => 'fas fa-calculator',
        'permission' => '' ,
        'subMenu' =>[
            [
                'name' => 'chart_of_accounts',
                'url' => 'tree-of-accounts',
                'permission' => '' ,
            ],
        ]
    ],
    [
        'name' => 'Establishment',
        'url' => 'dashboard',
        'icon' => 'fas fa-building',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'CRM',
        'url' => 'dashboard',
        'icon' => 'fas fa-cogs',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'Employees',
        'url' => 'dashboard',
        'icon' => 'fas fa-id-card',
        'permission' => '' ,
        'subMenu' =>[]
    ],
    [
        'name' => 'Reports',
        'url' => 'dashboard',
        'icon' => 'fas fa-bar-chart',
        'permission' => '' ,
        'subMenu' =>[]
    ],
];