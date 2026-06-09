<?php

/**
 * Sidebar "active" detection for modules that use a unified entry URL
 * but serve content on separate routes (e.g. franchise).
 */
return [
    'franchise' => [
        'tabs_config' => 'franchise_hub_tabs',
        'path_prefix' => 'franchise',
        'route_prefixes' => ['franchise.'],
        'extra_routes' => [
            'approve-action',
        ],
    ],
];
