<?php

use Arseno25\LaravelApiMagic\Parsers\DatabaseColumnMetadataInferrer;
use Arseno25\LaravelApiMagic\Parsers\DatabaseSchemaParser;
use Illuminate\Support\Facades\Schema;

uses()->group('parsers', 'database-schema-parser');

beforeEach(function () {
    $this->inferrer = new DatabaseColumnMetadataInferrer;
});

it('normalizes schema-qualified table names', function () {
    expect($this->inferrer->normalizeTableName('public.users'))->toBe('users');
    expect($this->inferrer->normalizeTableName('main.products'))->toBe(
        'products',
    );
    expect($this->inferrer->normalizeTableName('[dbo].[orders]'))->toBe(
        'orders',
    );
});

it('infers sqlite tinyint(1) columns as boolean', function () {
    $metadata = $this->inferrer->infer(
        [
            'name' => 'active',
            'type_name' => 'tinyint',
            'type' => 'tinyint(1)',
            'nullable' => false,
        ],
        'sqlite',
    );

    expect($metadata['type'])->toBe('boolean');
    expect($metadata['cast'])->toBe('boolean');
});

it('infers mysql json columns as array casts', function () {
    $metadata = $this->inferrer->infer(
        [
            'name' => 'payload',
            'type_name' => 'json',
            'type' => 'json',
            'nullable' => true,
        ],
        'mysql',
    );

    expect($metadata['type'])->toBe('json');
    expect($metadata['cast'])->toBe('array');
});

it('distinguishes floating and fixed-scale numeric casts', function () {
    $floatMetadata = $this->inferrer->infer(
        [
            'name' => 'exchange_rate',
            'type_name' => 'double',
            'type' => 'double precision',
            'nullable' => false,
        ],
        'pgsql',
    );

    $decimalMetadata = $this->inferrer->infer(
        [
            'name' => 'price',
            'type_name' => 'decimal',
            'type' => 'decimal(10,4)',
            'scale' => 4,
            'nullable' => false,
        ],
        'mysql',
    );

    expect($floatMetadata['type'])->toBe('float');
    expect($floatMetadata['cast'])->toBe('float');
    expect($decimalMetadata['type'])->toBe('decimal');
    expect($decimalMetadata['cast'])->toBe('decimal:4');
});

it('infers postgres jsonb and timestamptz columns correctly', function () {
    $jsonMetadata = $this->inferrer->infer(
        [
            'name' => 'payload',
            'type_name' => 'jsonb',
            'type' => 'jsonb',
            'nullable' => false,
        ],
        'pgsql',
    );

    $timestampMetadata = $this->inferrer->infer(
        [
            'name' => 'published_at',
            'type_name' => 'timestamp',
            'type' => 'timestamp(0) with time zone',
            'nullable' => true,
        ],
        'pgsql',
    );

    expect($jsonMetadata['type'])->toBe('json');
    expect($jsonMetadata['cast'])->toBe('array');
    expect($timestampMetadata['type'])->toBe('datetime');
    expect($timestampMetadata['cast'])->toBe('datetime');
});

it('infers sql server bit and uniqueidentifier columns correctly', function () {
    $booleanMetadata = $this->inferrer->infer(
        [
            'name' => 'is_active',
            'type_name' => 'bit',
            'type' => 'bit',
            'nullable' => false,
        ],
        'sqlsrv',
    );

    $uuidMetadata = $this->inferrer->infer(
        [
            'name' => 'external_id',
            'type_name' => 'uniqueidentifier',
            'type' => 'uniqueidentifier',
            'nullable' => false,
        ],
        'sqlsrv',
    );

    expect($booleanMetadata['type'])->toBe('boolean');
    expect($booleanMetadata['cast'])->toBe('boolean');
    expect($uuidMetadata['type'])->toBe('uuid');
    expect($uuidMetadata['cast'])->toBeNull();
});

it('normalizes schema-qualified table listings in parser', function () {
    Schema::shouldReceive('getTableListing')
        ->once()
        ->andReturn([
            'main.migrations',
            'main.users',
            'public.products',
            '[dbo].[orders]',
        ]);

    $parser = new DatabaseSchemaParser(new DatabaseColumnMetadataInferrer);

    expect($parser->getTables())->toBe([
        'main.users',
        'public.products',
        '[dbo].[orders]',
    ]);
});
