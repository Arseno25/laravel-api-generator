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
