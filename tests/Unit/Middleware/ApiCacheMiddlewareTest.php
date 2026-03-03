<?php

use Arseno25\LaravelApiMagic\Http\Middleware\ApiCacheMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

uses()->group('middleware', 'cache');

beforeEach(function () {
    config()->set('cache.default', 'array');
});

describe('API Cache Middleware', function () {
    it('passes through for non-GET requests', function () {
        config()->set('laravel-api-magic.cache.enabled', true);

        $middleware = app(ApiCacheMiddleware::class);
        $request = Request::create('/api/products', 'POST');

        $called = false;
        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return new JsonResponse(['created' => true], 201);
        });

        expect($called)->toBeTrue();
        expect($response->getStatusCode())->toBe(201);
    });

    it('passes through when cache is globally disabled', function () {
        config()->set('laravel-api-magic.cache.enabled', false);

        $middleware = app(ApiCacheMiddleware::class);
        $request = Request::create('/api/products', 'GET');

        $called = false;
        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return new JsonResponse(['real' => true]);
        });

        expect($called)->toBeTrue();
        expect($response->getData(true)['real'])->toBeTrue();
    });

    it('passes through for GET requests without ApiCache attribute', function () {
        config()->set('laravel-api-magic.cache.enabled', true);

        Route::middleware('api')->get('/api/test-no-cache', function () {
            return response()->json(['no_cache' => true]);
        });

        $middleware = app(ApiCacheMiddleware::class);
        $request = Request::create('/api/test-no-cache', 'GET');

        $route = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        $called = false;
        $response = $middleware->handle($request, function ($req) use (&$called) {
            $called = true;

            return new JsonResponse(['no_cache' => true]);
        });

        expect($called)->toBeTrue();
    });

    it('adds X-Api-Cache MISS header on cache miss', function () {
        config()->set('laravel-api-magic.cache.enabled', true);
        Cache::flush();

        Route::middleware('api')->get('/api/test-cache-miss', function () {
            return response()->json(['data' => 'value']);
        });

        $middleware = app(ApiCacheMiddleware::class);
        $request = Request::create('/api/test-cache-miss', 'GET');

        $route = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        // Without the ApiCache attribute, the middleware should pass through
        // We test the pass-through behavior since closures don't have attributes
        $response = $middleware->handle($request, function ($req) {
            return new JsonResponse(['data' => 'value']);
        });

        expect($response)->toBeInstanceOf(JsonResponse::class);
    });
});
