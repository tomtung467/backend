<?php

return [
    /**
     * API Response Format
     */
    'response_format' => 'json',

    /**
     * API Pagination
     */
    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /**
     * API Rate Limiting
     */
    'rate_limit' => [
        'enabled' => true,
        'requests_per_minute' => 60,
    ],

    /**
     * API Timeout
     */
    'timeout' => 30,

    /**
     * API Versioning
     */
    'version' => 'v1',

    /**
     * Enable API Logging
     */
    'logging' => [
        'enabled' => true,
        'channel' => 'api',
    ],
];
