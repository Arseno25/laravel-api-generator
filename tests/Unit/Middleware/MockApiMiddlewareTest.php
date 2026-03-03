<?php

use Arseno25\LaravelApiMagic\Http\Middleware\MockApiMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class MockTestController
{
    public function index()
    {
        return new JsonResponse(['real' => true]);
    }
}

uses()->group('middleware', 'mock');

describe('Mock API Middleware', function () {
    it('passes through when mock is not enabled', function () {
        config()->set('laravel-api-magic.mock.enabled', false);

        $middleware = app(MockApiMiddleware::class);
        $request = Request::create('/api/products', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new JsonResponse(['real' => true]);
        });

        expect($response->getData(true))->toHaveKey('real');
        expect($response->getData(true)['real'])->toBeTrue();
    });

    it('returns mock data when X-Api-Mock header is present', function () {
        config()->set('laravel-api-magic.mock.enabled', false);

        // Register a route so the middleware can resolve controller info
        Route::middleware('api')->get('/api/test-mock-items', 'MockTestController@index');

        $middleware = app(MockApiMiddleware::class);
        $request = Request::create('/api/test-mock-items', 'GET');
        $request->headers->set('X-Api-Mock', 'true');

        // Simulate route resolution
        $route = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        $response = $middleware->handle($request, function ($req) {
            return new JsonResponse(['real' => true]);
        });

        expect($response->getData(true))->toHaveKey('data');
        expect($response->getData(true))->toHaveKey('_mock');
        expect($response->getData(true)['_mock'])->toBeTrue();
        expect($response->headers->get('X-Api-Mock'))->toBe('true');
    });

    it('returns mock data when globally enabled', function () {
        config()->set('laravel-api-magic.mock.enabled', true);

        Route::middleware('api')->get('/api/test-global-mock', 'MockTestController@index');

        $middleware = app(MockApiMiddleware::class);
        $request = Request::create('/api/test-global-mock', 'GET');

        $route = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        $response = $middleware->handle($request, function ($req) {
            return new JsonResponse(['real' => true]);
        });

        expect($response->getData(true))->toHaveKey('_mock');
        expect($response->getData(true)['_mock'])->toBeTrue();
    });

    it('includes generated_at timestamp in mock response', function () {
        config()->set('laravel-api-magic.mock.enabled', false);

        Route::middleware('api')->get('/api/test-mock-timestamp', 'MockTestController@index');

        $middleware = app(MockApiMiddleware::class);
        $request = Request::create('/api/test-mock-timestamp', 'GET');
        $request->headers->set('X-Api-Mock', 'true');

        $route = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $route);

        $response = $middleware->handle($request, function ($req) {
            return new JsonResponse([]);
        });

        expect($response->getData(true))->toHaveKey('_generated_at');
    });
});
