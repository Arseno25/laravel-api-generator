<?php

use Arseno25\LaravelApiMagic\Services\OpenApiSchemaValidator;

uses()->group('unit', 'openapi-validator');

it('accepts a valid openapi schema', function () {
    $validator = new OpenApiSchemaValidator;

    $issues = $validator->validate([
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Example API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/users' => [
                'get' => [
                    'operationId' => 'listUsers',
                    'responses' => [
                        '200' => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/UserList',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
            'schemas' => [
                'UserList' => [
                    'type' => 'object',
                    'properties' => [
                        'data' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    expect($issues)->toBe([]);
});

it('detects unresolved refs and duplicate operation ids', function () {
    $validator = new OpenApiSchemaValidator;

    $issues = $validator->validate([
        'openapi' => '3.0.0',
        'info' => [
            'title' => 'Broken API',
            'version' => '1.0.0',
        ],
        'paths' => [
            '/users' => [
                'get' => [
                    'operationId' => 'usersIndex',
                    'responses' => [
                        '200' => [
                            'description' => 'OK',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        '$ref' => '#/components/schemas/MissingSchema',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/teams' => [
                'get' => [
                    'operationId' => 'usersIndex',
                    'responses' => [
                        '200' => [
                            'description' => 'OK',
                        ],
                    ],
                    'security' => [
                        ['missingScheme' => []],
                    ],
                ],
            ],
        ],
        'components' => [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                ],
            ],
            'schemas' => [],
        ],
    ]);

    expect($issues)->not->toBeEmpty();
    expect(implode("\n", $issues))
        ->toContain('Duplicate operationId [usersIndex]')
        ->toContain('Reference [#/components/schemas/MissingSchema] could not be resolved.')
        ->toContain('references unknown security scheme [missingScheme]');
});
