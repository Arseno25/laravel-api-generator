<?php

uses()->group('commands', 'validate-openapi');

it('validates the generated openapi schema', function () {
    $this->artisan('api-magic:validate')
        ->expectsOutput('Validating OpenAPI schema...')
        ->expectsOutput('OpenAPI schema is valid.')
        ->assertExitCode(0);
});
