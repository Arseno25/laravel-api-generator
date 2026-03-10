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

        // Asset sources used by the interactive docs UI
        'assets' => [
            'tailwind_cdn' => env(
                'API_MAGIC_DOCS_TAILWIND_CDN',
                'https://cdn.tailwindcss.com',
            ),
            'icon_stylesheet' => env('API_MAGIC_DOCS_ICON_STYLESHEET', null),
            'stylesheets' => [],
            'scripts' => [],
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

    /*
    |--------------------------------------------------------------------------
    | Server Environments
    |--------------------------------------------------------------------------
    |
    | Define multiple API server environments for documentation.
    | These appear as a selectable dropdown in the docs UI.
    |
    */
    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost'),
            'description' => 'Current Environment',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Health Telemetry
    |--------------------------------------------------------------------------
    |
    | Track API endpoint response times, error rates, and usage.
    |
    */
    'health' => [
        // Enable or disable health telemetry
        'enabled' => env('API_MAGIC_HEALTH_ENABLED', false),

        // Cache store for metrics (null = default)
        'store' => env('API_MAGIC_HEALTH_STORE', null),

        // Rolling metrics window in minutes
        'window' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Schema Changelog
    |--------------------------------------------------------------------------
    |
    | Automatically track changes to API schema over time.
    |
    */
    'changelog' => [
        // Enable changelog tracking
        'enabled' => env('API_MAGIC_CHANGELOG_ENABLED', false),

        // Storage path for schema snapshots
        'storage_path' => storage_path('api-magic/changelog'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth2 Configuration
    |--------------------------------------------------------------------------
    |
    | Configure OAuth2 authentication for the API documentation UI.
    | This enables the "Login with OAuth" button in the docs interface.
    |
    */
    'oauth' => [
        // OAuth2 authorization URL
        'auth_url' => env('API_MAGIC_OAUTH_AUTH_URL', ''),

        // OAuth2 client ID
        'client_id' => env('API_MAGIC_OAUTH_CLIENT_ID', ''),

        // OAuth2 scopes (comma-separated or array)
        'scopes' => env('API_MAGIC_OAUTH_SCOPES', []),
    ],
];
