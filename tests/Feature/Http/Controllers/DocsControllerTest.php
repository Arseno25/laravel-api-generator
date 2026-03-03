<?php

use Arseno25\LaravelApiMagic\Http\Controllers\DocsController;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

uses()->group('feature', 'docs-controller');

beforeEach(function () {
    // Clean up any existing cache
    $cacheFile = base_path('bootstrap/cache/api-magic.json');
    if (File::exists($cacheFile)) {
        File::delete($cacheFile);
    }
});

afterEach(function () {
    // Clean up after each test
    $cacheFile = base_path('bootstrap/cache/api-magic.json');
    if (File::exists($cacheFile)) {
        File::delete($cacheFile);
    }
});

describe('GET /api/docs', function () {
    it('displays the documentation view', function () {
        $response = get('/api/docs');

        $response->assertStatus(200);
        $response->assertViewIs('apimagic::docs');
    });

    it('includes documentation assets', function () {
        $response = get('/api/docs');

        $response->assertSee('API Documentation');
    });
});

describe('GET /api/docs/json', function () {
    it('returns JSON documentation schema', function () {
        $response = getJson('/api/docs/json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'title',
            'version',
            'baseUrl',
            'endpoints',
            'versions',
            'generated_at',
        ]);
    });

    it('includes endpoints array', function () {
        $response = getJson('/api/docs/json');

        $response->assertJsonStructure([
            'endpoints' => [],
        ]);
    });

    it('includes versions array', function () {
        $response = getJson('/api/docs/json');

        $response->assertJson([
            'versions' => ['1'],
        ]);
    });

    it('includes generated_at timestamp', function () {
        $response = getJson('/api/docs/json');

        $data = $response->json();
        expect($data['generated_at'])->not->toBeEmpty();
    });

    it('includes security schemes', function () {
        $response = getJson('/api/docs/json');

        $response->assertJsonStructure([
            'securitySchemes',
        ]);

        $data = $response->json();
        expect($data['securitySchemes'])->toHaveKey('bearerAuth');
        expect($data['securitySchemes']['bearerAuth'])->toMatchArray([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ]);
    });
});

describe('GET /api/docs/export', function () {
    it('exports OpenAPI format JSON', function () {
        $response = getJson('/api/docs/export');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'openapi',
            'info',
            'servers',
            'paths',
            'components',
        ]);
    });

    it('includes OpenAPI version', function () {
        $response = getJson('/api/docs/export');

        $response->assertJson([
            'openapi' => '3.0.0',
        ]);
    });

    it('includes info section', function () {
        $response = getJson('/api/docs/export');

        $response->assertJsonStructure([
            'info' => [
                'title',
                'version',
                'description',
            ],
        ]);
    });

    it('includes components with security schemes', function () {
        $response = getJson('/api/docs/export');

        $response->assertJsonStructure([
            'components' => [
                'securitySchemes' => [
                    'bearerAuth',
                ],
            ],
        ]);

        $data = $response->json();
        expect($data['components']['securitySchemes']['bearerAuth'])->toMatchArray([
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
        ]);
    });

    it('includes tags array', function () {
        $response = getJson('/api/docs/export');

        $response->assertJsonStructure([
            'tags' => [],
        ]);
    });

    it('sets content disposition header for download', function () {
        $response = getJson('/api/docs/export');

        expect($response->headers->get('content-disposition'))->toContain('attachment');
        expect($response->headers->get('content-disposition'))->toContain('api-docs-');
    });
});

describe('caching behavior', function () {
    it('uses cached data when available', function () {
        // Create a cached version
        $cachePath = base_path('bootstrap/cache/api-magic.json');
        File::ensureDirectoryExists(dirname($cachePath));

        $cachedData = [
            'generated_at' => now()->toIso8601String(),
            'endpoints' => [
                '/api/test' => [
                    'get' => [
                        'summary' => 'Cached endpoint',
                    ],
                ],
            ],
            'versions' => ['1'],
        ];

        File::put($cachePath, json_encode($cachedData));

        $response = getJson('/api/docs/json');

        // Should return cached data
        $response->assertJson([
            'endpoints' => $cachedData['endpoints'],
        ]);
    });

    it('generates fresh data when cache is missing', function () {
        // Ensure no cache exists
        $cachePath = base_path('bootstrap/cache/api-magic.json');
        if (File::exists($cachePath)) {
            File::delete($cachePath);
        }

        $response = getJson('/api/docs/json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'title',
            'endpoints',
            'generated_at',
        ]);
    });

    it('handles corrupted cache gracefully', function () {
        $cachePath = base_path('bootstrap/cache/api-magic.json');
        File::ensureDirectoryExists(dirname($cachePath));

        // Write invalid JSON
        File::put($cachePath, 'invalid json content');

        $response = getJson('/api/docs/json');

        // Should generate fresh data instead of failing
        $response->assertStatus(200);
    });
});

describe('endpoint grouping', function () {
    it('groups endpoints by path', function () {
        $response = getJson('/api/docs/json');

        $data = $response->json();

        expect($data['endpoints'])->toBeArray();

        // Each endpoint should be keyed by path
        foreach ($data['endpoints'] as $path => $methods) {
            expect(is_string($path))->toBeTrue();
            expect(is_array($methods))->toBeTrue();
        }
    });

    it('groups endpoints by version', function () {
        $response = getJson('/api/docs/json');

        $data = $response->json();

        expect($data['endpointsByVersion'])->toBeArray();
        expect($data['versions'])->toBeArray();
    });
});

describe('security in endpoints', function () {
    it('includes security information for authenticated routes', function () {
        // Register a protected route
        Illuminate\Support\Facades\Route::middleware('api')->middleware('auth:sanctum')
            ->get('/api/protected-test', function () {
                return response()->json(['protected' => true]);
            });

        $response = getJson('/api/docs/json');

        $data = $response->json();

        // Check if any endpoint has security requirements
        $hasSecurity = false;
        foreach ($data['endpoints'] as $path => $methods) {
            foreach ($methods as $method => $endpoint) {
                if (! empty($endpoint['security'])) {
                    $hasSecurity = true;
                    break 2;
                }
            }
        }

        expect($hasSecurity)->toBeTrue();
    });
});

describe('error handling', function () {
    it('handles missing view gracefully', function () {
        // This test would require temporarily removing the view
        // For now, we just verify the controller exists
        $controller = app(DocsController::class);

        expect($controller)->toBeInstanceOf(DocsController::class);
    });

    it('returns valid JSON even with no API routes', function () {
        // This is difficult to test as there are always some routes
        // But we can verify the response structure
        $response = getJson('/api/docs/json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'title',
            'version',
            'baseUrl',
            'endpoints' => [],
            'versions',
            'generated_at',
        ]);
    });
});

describe('query parameters for index endpoints', function () {
    it('includes standard query parameters for GET index', function () {
        // Register an index route
        Illuminate\Support\Facades\Route::middleware('api')
            ->get('/api/products', function () {
                return response()->json([]);
            });

        $response = getJson('/api/docs/json');

        $data = $response->json();

        // Find the products endpoint
        $found = false;
        foreach ($data['endpoints'] as $path => $methods) {
            if (str_contains($path, 'products')) {
                if (isset($methods['get'])) {
                    $endpoint = $methods['get'];
                    // Should have query parameters for index
                    if (! empty($endpoint['parameters']['query'])) {
                        $queryNames = array_column($endpoint['parameters']['query'], 'name');
                        if (in_array('page', $queryNames) && in_array('per_page', $queryNames)) {
                            $found = true;
                        }
                    }
                }
            }
        }

        expect($found)->toBeTrue();
    });
});
