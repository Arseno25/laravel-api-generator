<?php

use Illuminate\Support\Facades\File;

uses()->group('commands', 'export');

beforeEach(function () {
    $this->outputPath = base_path('build/test-api-docs.json');

    if (File::exists($this->outputPath)) {
        File::delete($this->outputPath);
    }
});

afterEach(function () {
    if (File::exists($this->outputPath)) {
        File::delete($this->outputPath);
    }
});

it(
    'exports an openapi document through the shared schema builder',
    function () {
        $this->artisan('api-magic:export', [
            '--path' => $this->outputPath,
            '--format' => 'json',
        ])->assertExitCode(0);

        expect(File::exists($this->outputPath))->toBeTrue();

        $schema = json_decode(File::get($this->outputPath), true);

        expect($schema)
            ->toBeArray()
            ->toHaveKey('openapi')
            ->toHaveKey('components.schemas')
            ->toHaveKey('components.securitySchemes.bearerAuth');
    },
);

it('supports strict mode when exporting openapi documents', function () {
    $this->artisan('api-magic:export', [
        '--path' => $this->outputPath,
        '--format' => 'json',
        '--strict' => true,
    ])->assertExitCode(0);

    expect(File::exists($this->outputPath))->toBeTrue();
});
