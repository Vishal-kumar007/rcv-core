<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Core Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the Core module.
    |
    */

    'name' => 'Core',
    'description' => 'Core module providing base functionality for other modules',

    /*
    |--------------------------------------------------------------------------
    | Module Settings
    |--------------------------------------------------------------------------
    |
    | General settings for the module.
    |
    */
    'settings' => [
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // Time to live in seconds
        ],
        'pagination' => [
            'per_page' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers that should be registered.
    |
    */
    'providers' => [
        \Rcv\Core\Providers\CoreServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Aliases
    |--------------------------------------------------------------------------
    |
    | The aliases that should be registered.
    |
    */
    'aliases' => [
        // Add your aliases here
    ],
]; 