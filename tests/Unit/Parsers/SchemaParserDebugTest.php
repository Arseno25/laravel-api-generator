<?php

use Arseno25\LaravelApiMagic\Parsers\SchemaParser;

uses()->group('parsers', 'schema-parser');

beforeEach(function () {
    $this->parser = new SchemaParser;
});

it('debugs required rule parsing', function () {
    $result = $this->parser->parse('name:string|required');

    echo "\n=== DEBUG OUTPUT ===\n";
    echo "Rules output:\n";
    echo $result['rules'] . "\n";
    echo "===================\n\n";

    expect($result['rules'])->toContain("'name' => 'required'");
});
