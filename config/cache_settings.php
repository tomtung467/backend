<?php

return [
    /**
     * Default cache duration in minutes
     */
    'cache_duration' => 60,

    /**
     * Cache keys configuration
     */
    'cache_keys' => [
        'menu_categories' => 'menu_categories',
        'menu_foods' => 'menu_foods',
        'orders' => 'orders',
        'tables' => 'tables',
        'employees' => 'employees',
        'inventory' => 'inventory',
    ],

    /**
     * Cache tags
     */
    'cache_tags' => [
        'menu' => 'menu',
        'orders' => 'orders',
        'inventory' => 'inventory',
        'tables' => 'tables',
    ],
];
