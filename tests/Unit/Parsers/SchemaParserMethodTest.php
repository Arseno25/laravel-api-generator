<?php

use Arseno25\LaravelApiMagic\Parsers\SchemaParser;

uses()->group('parsers', 'schema-parser');

it('tests isRequired method directly using reflection', function () {
    $parser = new SchemaParser();

    // Use reflection to access private method
    $reflection = new ReflectionClass($parser);
    $method = $reflection->getMethod('isRequired');
    $method->setAccessible(true);

    expect($method->invoke($parser, 'required'))->toBeTrue();
    expect($method->invoke($parser, 'nullable'))->toBeFalse();
    expect($method->invoke($parser, 'email|required'))->toBeTrue();
    expect($method->invoke($parser, ''))->toBeFalse();
});

it('tests parseRules method directly using reflection', function () {
    $parser = new SchemaParser();

    // Use reflection to access private method
    $reflection = new ReflectionClass($parser);
    $method = $reflection->getMethod('parseRules');
    $method->setAccessible(true);

    $result = $method->invoke($parser, 'required');
    expect($result)->toBe(['required']);

    $result = $method->invoke($parser, 'email|required');
    expect($result)->toBe(['email', 'required']);
});

it('tests full parse with name:string|required', function () {
    $parser = new SchemaParser();
    $result = $parser->parse('name:string|required');

    echo "\nFull result:\n";
    print_r($result);
});
