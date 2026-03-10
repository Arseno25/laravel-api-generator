<?php

use Arseno25\LaravelApiMagic\Http\Middleware\ApiHealthMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

describe('API Health Middleware', function () {
    beforeEach(function () {
        config()->set('cache.default', 'array');
        Cache::flush();
    });

    it('passes through when health is disabled', function () {
        config()->set('api-magic.health.enabled', false);

        $middleware = app(ApiHealthMiddleware::class);
        $request = Request::create('/api/products', 'GET');

        $called = false;
        $response = $middleware->handle($request, function ($req) use (
            &$called,
        ) {
            $called = true;

            return new JsonResponse(['data' => true]);
        });

        expect($called)->toBeTrue();
        expect($response->getStatusCode())->toBe(200);
    });

    it('records metrics when health is enabled', function () {
        config()->set('api-magic.health.enabled', true);

        $middleware = app(ApiHealthMiddleware::class);
        $request = Request::create('/api/products', 'GET');

        $middleware->handle($request, function ($req) {
            return new JsonResponse(['data' => true]);
        });

        $metrics = ApiHealthMiddleware::getMetrics();

        expect($metrics)->not->toBeEmpty();
        expect($metrics[0]['total_requests'])->toBe(1);
        expect($metrics[0]['avg_response_ms'])->toBeGreaterThan(0);
    });

    it('tracks error rates', function () {
        config()->set('api-magic.health.enabled', true);

        $middleware = app(ApiHealthMiddleware::class);
        $request = Request::create('/api/fail', 'POST');

        $middleware->handle($request, function ($req) {
            return new JsonResponse(['error' => 'fail'], 500);
        });

        $metrics = ApiHealthMiddleware::getMetrics();

        expect($metrics)->not->toBeEmpty();
        expect($metrics[0]['total_errors'])->toBe(1);
        expect($metrics[0]['error_rate'])->toBe(100.0);
    });
});
