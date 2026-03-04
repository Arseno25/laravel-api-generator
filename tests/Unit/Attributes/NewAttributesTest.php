<?php

use Arseno25\LaravelApiMagic\Attributes\ApiDeprecated;
use Arseno25\LaravelApiMagic\Attributes\ApiExample;
use Arseno25\LaravelApiMagic\Attributes\ApiResponse;
use Arseno25\LaravelApiMagic\Attributes\ApiWebhook;

describe('New Attributes', function () {
    it('creates ApiDeprecated with defaults', function () {
        $attr = new ApiDeprecated;

        expect($attr->message)->toBe('');
        expect($attr->since)->toBeNull();
        expect($attr->alternative)->toBeNull();
    });

    it('creates ApiDeprecated with values', function () {
        $attr = new ApiDeprecated(
            message: 'Use /v2/users instead',
            since: 'v1.5.0',
            alternative: '/api/v2/users'
        );

        expect($attr->message)->toBe('Use /v2/users instead');
        expect($attr->since)->toBe('v1.5.0');
        expect($attr->alternative)->toBe('/api/v2/users');
    });

    it('creates ApiResponse with defaults', function () {
        $attr = new ApiResponse;

        expect($attr->status)->toBe(200);
        expect($attr->resource)->toBeNull();
        expect($attr->description)->toBe('');
        expect($attr->example)->toBeNull();
        expect($attr->isArray)->toBeFalse();
    });

    it('creates ApiResponse with all values', function () {
        $attr = new ApiResponse(
            status: 201,
            resource: 'App\\Http\\Resources\\UserResource',
            description: 'User created successfully',
            example: ['id' => 1, 'name' => 'John'],
            isArray: false
        );

        expect($attr->status)->toBe(201);
        expect($attr->resource)->toBe('App\\Http\\Resources\\UserResource');
        expect($attr->description)->toBe('User created successfully');
        expect($attr->example)->toBe(['id' => 1, 'name' => 'John']);
    });

    it('creates ApiExample attribute', function () {
        $attr = new ApiExample(
            request: ['name' => 'John', 'email' => 'john@example.com'],
            response: ['id' => 1, 'name' => 'John']
        );

        expect($attr->request)->toBe(['name' => 'John', 'email' => 'john@example.com']);
        expect($attr->response)->toBe(['id' => 1, 'name' => 'John']);
    });

    it('creates ApiWebhook attribute', function () {
        $attr = new ApiWebhook(
            event: 'order.completed',
            description: 'Fired when an order is completed',
            payload: ['order_id' => 'integer', 'total' => 'float']
        );

        expect($attr->event)->toBe('order.completed');
        expect($attr->description)->toBe('Fired when an order is completed');
        expect($attr->payload)->toBe(['order_id' => 'integer', 'total' => 'float']);
    });
});
