<?php

use Arseno25\LaravelApiMagic\Parsers\ResourceAnalyzer;

uses()->group('unit', 'resource-analyzer');

// We test the public analyze() method which orchestrates extraction
it('returns null for non-existent methods', function () {
    $analyzer = new ResourceAnalyzer;

    $result = $analyzer->analyze('NonExistentController', 'index');

    expect($result)->toBeNull();
});

// Since ResourceAnalyzer tests require actual reflection on real classes,
// creating a stub inline isn't easily parsed by file_get_contents in extractFromSource,
// but extractFromModel and extractFromDocBlock can be tested if we use a real class.
// We'll rely on the fact that analyze() correctly returns null if it doesn't match a JsonResource.
it('returns null for methods not returning JsonResource', function () {
    $analyzer = new ResourceAnalyzer;

    $class = new class
    {
        public function index(): string
        {
            return 'ok';
        }
    };

    $result = $analyzer->analyze(get_class($class), 'index');

    expect($result)->toBeNull();
});

it('prioritizes ApiMagicSchema attribute on the controller method', function () {
    $analyzer = new ResourceAnalyzer;

    $result = $analyzer->analyze(DummyMethodSchemaController::class, 'getAction');

    expect($result)->not->toBeNull();
    expect($result['name'])->toBe('CustomSchema');
    expect($result['schema']['foo']['type'])->toBe('boolean');
});

it('prioritizes ApiMagicSchema attribute on the resource class', function () {
    $analyzer = new ResourceAnalyzer;

    $result = $analyzer->analyze(DummyClassSchemaController::class, 'getAction');

    expect($result)->not->toBeNull();
    expect($result['name'])->toBe('DummyClassSchemaResource');
    expect($result['schema']['properties']->bar['type'])->toBe('integer');
});

use Illuminate\Http\Resources\Json\JsonResource;
use Arseno25\LaravelApiMagic\Attributes\ApiMagicSchema;

class DummyMethodSchemaController
{
    #[ApiMagicSchema(['foo' => ['type' => 'boolean']])]
    public function getAction(): JsonResource
    {
        return new class([]) extends JsonResource {};
    }
}

#[ApiMagicSchema(['bar' => ['type' => 'integer']])]
class DummyClassSchemaResource extends JsonResource
{
}

class DummyClassSchemaController
{
    public function getAction(): DummyClassSchemaResource
    {
        return new DummyClassSchemaResource([]);
    }
}
