<?php

use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\RouteAnalyzer;
use Illuminate\Support\Facades\Route;

uses()->group('parsers', 'route-analyzer');

beforeEach(function () {
    $this->requestAnalyzer = app(RequestAnalyzer::class);
    $this->routeAnalyzer = new RouteAnalyzer;
});

describe('API route detection', function () {
    it('identifies API routes by middleware', function () {
        // Register a test API route
        Route::middleware('api')->get('/api/test', function () {
            return response()->json(['test' => true]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();

        expect($routes)->not->toBeEmpty();
    });

    it('identifies API routes by URI prefix', function () {
        // Register a route with /api prefix (without explicit api middleware)
        Route::get('/api/by-prefix', function () {
            return response()->json(['test' => true]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();

        $apiUris = array_map(fn ($r) => $r->uri, $routes);
        expect($apiUris)->toContain('api/by-prefix');
    });

    it('excludes documentation endpoints', function () {
        // Register docs route
        Route::get('/api/docs', function () {
            return response()->json(['docs' => true]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();

        $docRoutes = array_filter($routes, fn ($r) => str_contains($r->uri, 'docs'));
        expect($docRoutes)->toBeEmpty();
    });

    it('excludes common package routes', function () {
        $excludedPatterns = ['sanctum', 'passport', 'telescope', 'horizon'];

        foreach ($excludedPatterns as $pattern) {
            Route::get("/api/{$pattern}/test", function () use ($pattern) {
                return response()->json(['package' => $pattern]);
            });
        }

        $routes = $this->routeAnalyzer->getApiRoutes();

        foreach ($excludedPatterns as $pattern) {
            $hasExcluded = collect($routes)->contains(fn ($r) => str_contains($r->uri, $pattern));
            expect($hasExcluded)->toBeFalse();
        }
    });
});

describe('route parsing', function () {
    it('parses GET route correctly', function () {
        Route::middleware('api')->get('/api/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => $r->uri === 'api/products');

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['method'])->toBe('get');
        expect($parsed['path'])->toBe('/api/products');
        expect($parsed['parameters']['path'])->toBeEmpty();
    });

    it('parses POST route correctly', function () {
        Route::middleware('api')->post('/api/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'products') && in_array('POST', $r->methods));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['method'])->toBe('post');
    });

    it('extracts path parameters', function () {
        Route::middleware('api')->get('/api/products/{id}', function ($id) {
            return response()->json(['id' => $id]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, '{id}'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['parameters']['path'])->toHaveCount(1);
        expect($parsed['parameters']['path'][0]['name'])->toBe('id');
        expect($parsed['parameters']['path'][0]['type'])->toBe('integer');
        expect($parsed['parameters']['path'][0]['required'])->toBeTrue();
    });

    it('extracts controller information', function () {
        // This would use actual controller
        $routes = $this->routeAnalyzer->getApiRoutes();

        foreach ($routes as $route) {
            $parsed = $this->routeAnalyzer->parseRoute($route, $this->requestAnalyzer);

            if ($parsed) {
                expect($parsed)->toHaveKey('controller');
                expect($parsed['controller'])->toBeArray();
            }
        }
    });
});

describe('version extraction', function () {
    it('extracts version from URI prefix', function () {
        Route::middleware('api')->get('/api/v2/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'v2'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['version'])->toBe('2');
    });

    it('defaults to version 1 when no version specified', function () {
        Route::middleware('api')->get('/api/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => $r->uri === 'api/products' && ! str_contains($r->uri, 'v'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['version'])->toBe('1');
    });

    it('extracts version from controller namespace', function () {
        // This would require actual V2 controller
        // For now, we test the extractVersion method directly
        $controllerInfo = [
            'full_path' => 'App\\Http\\Controllers\\Api\\V2\\ProductController',
            'controller' => 'ProductController',
            'method' => 'index',
        ];

        $version = $this->routeAnalyzer->extractVersion('api/products', $controllerInfo);

        expect($version)->toBe('2');
    });

    it('prioritizes URI version over namespace version', function () {
        // URI has v3 but namespace has V2
        $controllerInfo = [
            'full_path' => 'App\\Http\\Controllers\\Api\\V2\\ProductController',
            'controller' => 'ProductController',
            'method' => 'index',
        ];

        $version = $this->routeAnalyzer->extractVersion('api/v3/products', $controllerInfo);

        expect($version)->toBe('3');
    });
});

describe('tag generation', function () {
    it('generates tags from URI', function () {
        Route::middleware('api')->get('/api/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => $r->uri === 'api/products');

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['tags'])->toContain('Product');
    });

    it('adds version tag for non-v1 endpoints', function () {
        Route::middleware('api')->get('/api/v2/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'v2'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['tags'])->toContain('v2');
    });
});

describe('security analysis', function () {
    it('detects auth middleware', function () {
        Route::middleware('api')->middleware('auth:sanctum')->get('/api/protected', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'protected'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $hasBearerAuth = collect($parsed['security'])->contains(
            fn ($s) => isset($s['type']) && $s['type'] === 'http' && isset($s['scheme']) && $s['scheme'] === 'bearer'
        );

        expect($hasBearerAuth)->toBeTrue();
    });

    it('detects authorization gates', function () {
        Route::middleware('api')->middleware('can:update,post')->get('/api/posts/{id}', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'posts') && str_contains($r->uri, '{id}'));

        if ($testRoute) {
            $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

            $hasAuthz = collect($parsed['security'])->contains(
                fn ($s) => isset($s['type']) && $s['type'] === 'authorization'
            );

            expect($hasAuthz)->toBeTrue();
        }
    });

    it('detects rate limiting', function () {
        Route::middleware('api')->middleware('throttle:60,1')->get('/api/throttled', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'throttled'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $hasRateLimit = collect($parsed['security'])->contains(
            fn ($s) => isset($s['type']) && $s['type'] === 'rateLimit'
        );

        expect($hasRateLimit)->toBeTrue();
    });
});

describe('summary and description generation', function () {
    it('generates summary for route', function () {
        Route::middleware('api')->get('/api/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => $r->uri === 'api/products');

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        expect($parsed['summary'])->not->toBeEmpty();
        expect($parsed['description'])->not->toBeEmpty();
    });
});

describe('resource name extraction', function () {
    it('extracts resource name from URI', function () {
        // We can't test private method directly, but we can observe the behavior
        Route::middleware('api')->get('/api/products', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => $r->uri === 'api/products');

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        // Summary should contain the resource name
        expect($parsed['summary'])->toContain('Product');
    });
});
