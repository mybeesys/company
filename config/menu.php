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
        'name' => 'User Management',
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
        'name' => 'Product',
        'url' => '/',
        'icon' => 'fas fa-shopping-cart',
        'permission' => '' ,
        'subMenu' => [
            [ 
                'name' => 'Products',
                'url' => '/',
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
        'name' => 'Finance',
        'url' => '/',
        'icon' => 'fas fa-calculator',
        'permission' => '' ,
        'subMenu' =>[]
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

