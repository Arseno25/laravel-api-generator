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
        'default_version' => 1,

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
];
