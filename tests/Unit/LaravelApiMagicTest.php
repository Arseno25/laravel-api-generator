<?php

use Arseno25\LaravelApiMagic\LaravelApiMagic;

uses()->group('core', 'hooks');

beforeEach(function () {
    // Reset static state between tests via reflection
    $ref = new ReflectionClass(LaravelApiMagic::class);

    $before = $ref->getProperty('beforeParseCallbacks');
    $before->setAccessible(true);
    $before->setValue(null, []);

    $after = $ref->getProperty('afterParseCallbacks');
    $after->setAccessible(true);
    $after->setValue(null, []);
});

describe('Plugin hooks', function () {
    it('registers and calls beforeParse callbacks', function () {
        $called = false;
        LaravelApiMagic::beforeParse(function () use (&$called) {
            $called = true;
        });

        LaravelApiMagic::callBeforeParse();

        expect($called)->toBeTrue();
    });

    it('registers and calls afterParse callbacks with schema reference', function () {
        LaravelApiMagic::afterParse(function (array &$schema) {
            $schema['custom_key'] = 'injected_value';
        });

        $schema = ['endpoints' => []];
        LaravelApiMagic::callAfterParse($schema);

        expect($schema)->toHaveKey('custom_key');
        expect($schema['custom_key'])->toBe('injected_value');
    });

    it('calls multiple hooks in order', function () {
        $order = [];

        LaravelApiMagic::beforeParse(function () use (&$order) {
            $order[] = 'first';
        });
        LaravelApiMagic::beforeParse(function () use (&$order) {
            $order[] = 'second';
        });

        LaravelApiMagic::callBeforeParse();

        expect($order)->toBe(['first', 'second']);
    });

    it('does not fail when no hooks are registered', function () {
        LaravelApiMagic::callBeforeParse();
        $schema = ['test' => true];
        LaravelApiMagic::callAfterParse($schema);

        expect($schema)->toHaveKey('test');
    });
});

describe('Core service', function () {
    it('returns a version string', function () {
        $magic = new LaravelApiMagic;
        expect($magic->version())->toBeString();
    });

    it('returns docs enabled as boolean', function () {
        $magic = new LaravelApiMagic;
        expect($magic->docsEnabled())->toBeBool();
    });

    it('returns exclude patterns as array', function () {
        $magic = new LaravelApiMagic;
        expect($magic->excludePatterns())->toBeArray();
    });
});
