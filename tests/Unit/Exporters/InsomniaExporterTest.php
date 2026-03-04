<?php

use Arseno25\LaravelApiMagic\Exporters\InsomniaExporter;

uses()->group('unit', 'insomnia-exporter');

it('exports to insomnia format', function () {
    $schema = [
        'title' => 'Test API',
        'version' => '1.0.0',
        'baseUrl' => 'http://localhost',
        'endpoints' => [
            '/api/users' => [
                'get' => [
                    'summary' => 'Get Users',
                    'tags' => ['Users'],
                    'parameters' => [
                        'query' => [
                            ['name' => 'page', 'type' => 'integer'],
                        ]
                    ],
                ]
            ],
            '/api/users/{id}' => [
                'post' => [
                    'summary' => 'Update User',
                    'tags' => ['Users'],
                    'security' => [['type' => 'http', 'scheme' => 'bearer']],
                    'parameters' => [
                        'path' => [
                            ['name' => 'id', 'type' => 'integer'],
                        ],
                        'body' => [
                            'name' => ['type' => 'string', 'required' => true],
                            'avatar' => ['type' => 'file', 'is_file' => true, 'required' => false],
                        ]
                    ],
                ]
            ]
        ]
    ];

    $exporter = new InsomniaExporter();
    $result = $exporter->export($schema, $schema['baseUrl']);

    // Verify root structure
    expect($result)->toHaveKeys(['_type', '__export_format', '__export_date', '__export_source', 'resources']);
    expect($result['_type'])->toBe('export');
    expect($result['__export_format'])->toBe(4);

    $resources = $result['resources'];
    
    // Verify Workspace exists
    $workspace = collect($resources)->firstWhere('_type', 'workspace');
    expect($workspace)->not->toBeNull();
    expect($workspace['name'])->toBe('Test API');

    // Verify Environment exists
    $environment = collect($resources)->firstWhere('_type', 'environment');
    expect($environment)->not->toBeNull();
    expect($environment['data']['base_url'])->toBe('http://localhost');

    // Verify Request Group (Folder) exists
    $requestGroup = collect($resources)->firstWhere('_type', 'request_group');
    expect($requestGroup)->not->toBeNull();
    expect($requestGroup['name'])->toBe('Users');

    // Verify Requests exist
    $requests = collect($resources)->where('_type', 'request')->values();
    expect($requests)->toHaveCount(2);

    $getRequest = $requests->firstWhere('method', 'GET');
    expect($getRequest['name'])->toBe('Get Users');
    expect($getRequest['url'])->toBe('{{ _.base_url }}/api/users');
    expect($getRequest['parameters'][0]['name'])->toBe('page');

    $postRequest = $requests->firstWhere('method', 'POST');
    expect($postRequest['name'])->toBe('Update User');
    expect($postRequest['url'])->toBe('{{ _.base_url }}/api/users/{id}');
    // Should have multipart body because of 'file' type in schema
    expect($postRequest['body']['mimeType'])->toBe('multipart/form-data');
    expect($postRequest['body']['params'])->toHaveCount(2);
    // Should have bearer auth
    expect($postRequest['authentication']['type'])->toBe('bearer');
    expect($postRequest['authentication']['token'])->toBe('{{ _.token }}');
});
