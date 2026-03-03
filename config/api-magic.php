<?php

// config for Arseno25/LaravelApiMagic
return [

    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    |
    | Configure the auto-generated API documentation feature.
    |
    */
    'docs' => [

        // Enable or disable the documentation routes
        'enabled' => env('API_MAGIC_DOCS_ENABLED', true),

        // URL prefix for documentation routes
        'prefix' => env('API_MAGIC_DOCS_PREFIX', 'docs'),

        // Middleware applied to documentation routes
        'middleware' => [],

        // Route patterns to exclude from documentation
        'exclude_patterns' => [
            'sanctum',
            'passport',
            'oauth',
            'telescope',
            'horizon',
            'ignition',
            '_ignition',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Generator Defaults
    |--------------------------------------------------------------------------
    |
    | Default options for the api:magic generator command.
    |
    */
    'generator' => [

        // Default API version
        'default_version' => null,

        // Default seeder count
        'seeder_count' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stubs
    |--------------------------------------------------------------------------
    |
    | Customize the stubs used for code generation.
    | Publish stubs via: php artisan vendor:publish --tag=api-magic-stubs
    |
    */
    'stubs' => [

        // Path to custom stubs directory (null = use package defaults)
        'path' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mock Server
    |--------------------------------------------------------------------------
    |
    | Enable the mock API server for frontend-first development.
    | When enabled, requests with X-Api-Mock: true header will get
    | fake data responses without hitting the database.
    |
    */
    'mock' => [

        // Enable or disable mock mode globally
        'enabled' => env('API_MAGIC_MOCK_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Response Caching
    |--------------------------------------------------------------------------
    |
    | Configure automatic response caching for GET endpoints
    | that use the #[ApiCache] attribute.
    |
    */
    'cache' => [

        // Enable or disable API caching globally
        'enabled' => env('API_MAGIC_CACHE_ENABLED', true),

        // Default cache store (null = use default cache driver)
        'store' => env('API_MAGIC_CACHE_STORE', null),
    ],
];
