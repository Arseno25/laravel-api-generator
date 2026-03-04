<?php

use Arseno25\LaravelApiMagic\Services\ChangelogService;
use Illuminate\Support\Facades\File;

describe('Changelog Service', function () {
    beforeEach(function () {
        $this->storagePath = storage_path('api-magic/changelog-test');
        config()->set('laravel-api-magic.changelog.storage_path', $this->storagePath);

        // Clean up test directory
        if (File::isDirectory($this->storagePath)) {
            File::deleteDirectory($this->storagePath);
        }
    });

    afterEach(function () {
        if (File::isDirectory($this->storagePath)) {
            File::deleteDirectory($this->storagePath);
        }
    });

    it('saves a schema snapshot', function () {
        $service = new ChangelogService;
        $schema = [
            'endpoints' => [
                '/api/users' => [
                    'get' => ['summary' => 'List users', 'parameters' => []],
                ],
            ],
        ];

        $path = $service->saveSnapshot($schema);

        expect(File::exists($path))->toBeTrue();
        $content = json_decode(File::get($path), true);
        expect($content['endpoints'])->toHaveKey('/api/users');
    });

    it('returns snapshots sorted by newest first', function () {
        $service = new ChangelogService;

        $service->saveSnapshot(['endpoints' => ['a' => []]]);
        sleep(1);
        $service->saveSnapshot(['endpoints' => ['b' => []]]);

        $snapshots = $service->getSnapshots();

        expect($snapshots)->toHaveCount(2);
        // Newest is first
        $latest = json_decode(File::get($snapshots[0]['path']), true);
        expect($latest['endpoints'])->toHaveKey('b');
    });

    it('computes diff between schemas', function () {
        $service = new ChangelogService;

        $oldSchema = [
            'endpoints' => [
                '/api/users' => [
                    'get' => ['summary' => 'List', 'parameters' => ['old_param']],
                ],
                '/api/removed' => [
                    'delete' => ['summary' => 'Remove'],
                ],
            ],
        ];

        $newSchema = [
            'endpoints' => [
                '/api/users' => [
                    'get' => ['summary' => 'List', 'parameters' => ['new_param']],
                ],
                '/api/products' => [
                    'get' => ['summary' => 'Products'],
                ],
            ],
        ];

        $diff = $service->computeDiff($oldSchema, $newSchema);

        expect($diff['total_added'])->toBe(1);
        expect($diff['total_removed'])->toBe(1);
        expect($diff['total_changed'])->toBe(1);
    });
});
