<?php

use Arseno25\LaravelApiMagic\Exporters\PostmanExporter;

uses()->group('exporters', 'postman');

beforeEach(function () {
    $this->exporter = new PostmanExporter;
});

describe('Postman Collection export', function () {
    it('generates valid Postman Collection v2.1 structure', function () {
        $schema = [
            'title' => 'Test API Documentation',
            'version' => '1.0.0',
            'endpoints' => [],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        expect($collection)->toHaveKey('info');
        expect($collection['info']['name'])->toBe('Test API Documentation');
        expect($collection['info']['schema'])->toContain('schema.getpostman.com');
        expect($collection)->toHaveKey('variable');
        expect($collection)->toHaveKey('auth');
        expect($collection)->toHaveKey('item');
    });

    it('includes base_url and token variables', function () {
        $schema = ['title' => 'Test', 'version' => '1.0.0', 'endpoints' => []];
        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        $variables = collect($collection['variable']);
        expect($variables->firstWhere('key', 'base_url')['value'])->toBe('http://localhost:8000');
        expect($variables->firstWhere('key', 'token'))->not->toBeNull();
    });

    it('organizes endpoints into folders by tag', function () {
        $schema = [
            'title' => 'Test',
            'version' => '1.0.0',
            'endpoints' => [
                '/api/products' => [
                    'get' => [
                        'method' => 'get',
                        'path' => '/api/products',
                        'summary' => 'List Products',
                        'tags' => ['Products'],
                        'parameters' => ['body' => [], 'path' => [], 'query' => []],
                        'security' => [],
                    ],
                ],
                '/api/users' => [
                    'get' => [
                        'method' => 'get',
                        'path' => '/api/users',
                        'summary' => 'List Users',
                        'tags' => ['Users'],
                        'parameters' => ['body' => [], 'path' => [], 'query' => []],
                        'security' => [],
                    ],
                ],
            ],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        expect($collection['item'])->toHaveCount(2);
        $folderNames = array_map(fn ($item) => $item['name'], $collection['item']);
        expect($folderNames)->toContain('Products');
        expect($folderNames)->toContain('Users');
    });

    it('generates request with correct method and URL', function () {
        $schema = [
            'title' => 'Test',
            'version' => '1.0.0',
            'endpoints' => [
                '/api/products' => [
                    'post' => [
                        'method' => 'post',
                        'path' => '/api/products',
                        'summary' => 'Create Product',
                        'tags' => ['Products'],
                        'parameters' => [
                            'body' => [
                                ['name' => 'title', 'type' => 'string', 'rules' => 'required'],
                                ['name' => 'price', 'type' => 'number', 'rules' => 'required'],
                            ],
                            'path' => [],
                            'query' => [],
                        ],
                        'security' => [],
                    ],
                ],
            ],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        $request = $collection['item'][0]['item'][0]['request'];
        expect($request['method'])->toBe('POST');
        expect($request['url']['raw'])->toContain('{{base_url}}');
        expect($request['body'])->not->toBeNull();
        expect($request['body']['mode'])->toBe('raw');
    });

    it('converts path parameters to Postman format', function () {
        $schema = [
            'title' => 'Test',
            'version' => '1.0.0',
            'endpoints' => [
                '/api/products/{id}' => [
                    'get' => [
                        'method' => 'get',
                        'path' => '/api/products/{id}',
                        'summary' => 'Show Product',
                        'tags' => ['Products'],
                        'parameters' => [
                            'body' => [],
                            'path' => [
                                ['name' => 'id', 'type' => 'integer'],
                            ],
                            'query' => [],
                        ],
                        'security' => [],
                    ],
                ],
            ],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        $request = $collection['item'][0]['item'][0]['request'];
        // Postman uses :param instead of {param}
        expect($request['url']['raw'])->toContain(':id');
        expect($request['url']['variable'])->not->toBeEmpty();
        expect($request['url']['variable'][0]['key'])->toBe('id');
    });

    it('adds auth header for secured endpoints', function () {
        $schema = [
            'title' => 'Test',
            'version' => '1.0.0',
            'endpoints' => [
                '/api/products' => [
                    'get' => [
                        'method' => 'get',
                        'path' => '/api/products',
                        'summary' => 'List Products',
                        'tags' => ['Products'],
                        'parameters' => ['body' => [], 'path' => [], 'query' => []],
                        'security' => [
                            ['type' => 'http', 'scheme' => 'bearer'],
                        ],
                    ],
                ],
            ],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        $request = $collection['item'][0]['item'][0]['request'];
        expect($request['auth']['type'])->toBe('bearer');
    });

    it('handles file upload fields with formdata mode', function () {
        $schema = [
            'title' => 'Test',
            'version' => '1.0.0',
            'endpoints' => [
                '/api/products' => [
                    'post' => [
                        'method' => 'post',
                        'path' => '/api/products',
                        'summary' => 'Create',
                        'tags' => ['Products'],
                        'parameters' => [
                            'body' => [
                                ['name' => 'image', 'type' => 'file', 'is_file' => true, 'rules' => 'required'],
                                ['name' => 'title', 'type' => 'string', 'rules' => 'required'],
                            ],
                            'path' => [],
                            'query' => [],
                        ],
                        'security' => [],
                    ],
                ],
            ],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        $request = $collection['item'][0]['item'][0]['request'];
        expect($request['body']['mode'])->toBe('formdata');
    });

    it('includes query params in URL', function () {
        $schema = [
            'title' => 'Test',
            'version' => '1.0.0',
            'endpoints' => [
                '/api/products' => [
                    'get' => [
                        'method' => 'get',
                        'path' => '/api/products',
                        'summary' => 'List',
                        'tags' => ['Products'],
                        'parameters' => [
                            'body' => [],
                            'path' => [],
                            'query' => [
                                ['name' => 'page', 'type' => 'integer', 'description' => 'Page number'],
                            ],
                        ],
                        'security' => [],
                    ],
                ],
            ],
        ];

        $collection = $this->exporter->export($schema, 'http://localhost:8000');

        $url = $collection['item'][0]['item'][0]['request']['url'];
        expect($url['query'][0]['key'])->toBe('page');
    });
});
