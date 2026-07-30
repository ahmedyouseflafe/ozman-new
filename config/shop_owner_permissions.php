<?php

return [
    'allowed' => [
        'dashboard.view', 'dashboard.main',
        'shops.view', 'shops.edit',
        'products.view', 'products.preview', 'products.create', 'products.edit', 'products.delete',
        'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
        'ads.view', 'ads.create', 'ads.edit', 'ads.delete',
        'screens.view', 'screens.create', 'screens.edit', 'screens.delete', 'screens.place_top', 'screens.place_bottom',
        'front_orders.view', 'front_orders.manage',
        'reward_wheels.purchase.view', 'reward_wheels.purchase.manage',
        'settings.view', 'settings.profile', 'settings.password',
    ],
    'catalog_type_permissions' => [
        'restaurant' => [
            'restaurant.view',
            'restaurant.tables.manage',
            'restaurant.orders.manage',
        ],
    ],
    'required' => ['dashboard.view', 'shops.view'],
    'catalog_type_required' => [
        'restaurant' => ['restaurant.view'],
    ],
];
