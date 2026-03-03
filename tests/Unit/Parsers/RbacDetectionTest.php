<?php

use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\RouteAnalyzer;
use Illuminate\Support\Facades\Route;

uses()->group('parsers', 'rbac');

beforeEach(function () {
    $this->requestAnalyzer = app(RequestAnalyzer::class);
    $this->routeAnalyzer = new RouteAnalyzer;
});

describe('RBAC auto-detection', function () {
    it('detects Spatie role middleware', function () {
        Route::middleware(['api', 'role:admin'])->get('/api/admin/dashboard', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'admin/dashboard'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $hasRole = collect($parsed['security'])->contains(
            fn ($s) => isset($s['type']) && $s['type'] === 'role'
        );

        expect($hasRole)->toBeTrue();
    });

    it('extracts multiple roles from pipe-separated middleware', function () {
        Route::middleware(['api', 'role:admin|editor|moderator'])->get('/api/admin/settings', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'admin/settings'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $roleEntry = collect($parsed['security'])->first(
            fn ($s) => isset($s['type']) && $s['type'] === 'role'
        );

        expect($roleEntry)->not->toBeNull();
        expect($roleEntry['roles'])->toContain('admin');
        expect($roleEntry['roles'])->toContain('editor');
        expect($roleEntry['roles'])->toContain('moderator');
        expect($roleEntry['roles'])->toHaveCount(3);
    });

    it('detects Spatie permission middleware', function () {
        Route::middleware(['api', 'permission:manage-users'])->get('/api/admin/users', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'admin/users'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $hasPermission = collect($parsed['security'])->contains(
            fn ($s) => isset($s['type']) && $s['type'] === 'permission'
        );

        expect($hasPermission)->toBeTrue();
    });

    it('extracts multiple permissions from pipe-separated middleware', function () {
        Route::middleware(['api', 'permission:edit-posts|delete-posts'])->get('/api/admin/posts', function () {
            return response()->json([]);
        });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'admin/posts'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $permEntry = collect($parsed['security'])->first(
            fn ($s) => isset($s['type']) && $s['type'] === 'permission'
        );

        expect($permEntry)->not->toBeNull();
        expect($permEntry['permissions'])->toContain('edit-posts');
        expect($permEntry['permissions'])->toContain('delete-posts');
    });

    it('combines auth + role + rate limit security entries', function () {
        Route::middleware(['api', 'auth:sanctum', 'role:admin', 'throttle:30,1'])
            ->get('/api/admin/critical', function () {
                return response()->json([]);
            });

        $routes = $this->routeAnalyzer->getApiRoutes();
        $testRoute = collect($routes)->first(fn ($r) => str_contains($r->uri, 'admin/critical'));

        expect($testRoute)->not->toBeNull();

        $parsed = $this->routeAnalyzer->parseRoute($testRoute, $this->requestAnalyzer);

        $types = collect($parsed['security'])->pluck('type')->all();
        expect($types)->toContain('http');       // auth
        expect($types)->toContain('role');       // role
        expect($types)->toContain('rateLimit');  // throttle
    });
});
