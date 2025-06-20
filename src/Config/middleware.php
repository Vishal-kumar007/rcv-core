<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | Define the middleware groups that can be used by modules.
    | Each group can have its own set of middleware.
    |
    */
    'groups' => [
        'web' => [
            // Web middleware group
        ],
        'api' => [
            // API middleware group
        ],
        'global' => [
            // Global middleware group
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Define the middleware aliases that can be used by modules.
    | These aliases can be used in route definitions.
    |
    */
    'aliases' => [
        // Middleware aliases
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Priority
    |--------------------------------------------------------------------------
    |
    | Define the priority order for middleware execution.
    | Middleware with higher priority will be executed first.
    |
    */
    'priority' => [
        // Middleware priority order
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Global middleware configuration settings.
    |
    */
    'config' => [
        // Enable/disable middleware registration
        'enabled' => true,

        // Enable/disable middleware validation
        'validate' => true,

        // Enable/disable middleware logging
        'logging' => true,

        // Maximum number of middleware per group
        'max_per_group' => 50,

        // Enable/disable middleware caching
        'cache' => true,

        // Cache duration in minutes
        'cache_duration' => 60,
    ],
]; 