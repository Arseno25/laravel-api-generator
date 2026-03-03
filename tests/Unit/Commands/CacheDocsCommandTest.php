<?php

use Arseno25\LaravelApiMagic\Commands\CacheDocsCommand;
use Illuminate\Support\Facades\File;

uses()->group('commands', 'cache-docs');

beforeEach(function () {
    // Clean up cache file before each test
    $cacheFile = base_path('bootstrap/cache/api-magic.json');
    if (File::exists($cacheFile)) {
        File::delete($cacheFile);
    }
});

afterAll(function () {
    // Final cleanup
    $cacheFile = base_path('bootstrap/cache/api-magic.json');
    if (File::exists($cacheFile)) {
        File::delete($cacheFile);
    }
});

it('has the correct signature', function () {
    $command = app(CacheDocsCommand::class);

    expect($command->getSignature())->toBe('api:magic:cache');
});

describe('cache generation', function () {
    it('generates cache file successfully', function () {
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        expect(base_path('bootstrap/cache/api-magic.json'))->toFileExist();
    });

    it('creates cache directory if not exists', function () {
        $cacheDir = base_path('bootstrap/cache');

        // Remove directory if exists
        if (File::isDirectory($cacheDir)) {
            File::deleteDirectory($cacheDir);
        }

        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        expect(File::isDirectory($cacheDir))->toBeTrue();
        expect(base_path('bootstrap/cache/api-magic.json'))->toFileExist();
    });

    it('generates valid json structure', function () {
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData)->toBeArray();
        expect($cachedData)->toHaveKeys(['generated_at', 'endpoints', 'endpointsByVersion', 'versions']);
    });
});

describe('--clear option', function () {
    it('clears existing cache file', function () {
        // First create cache
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        expect(base_path('bootstrap/cache/api-magic.json'))->toFileExist();

        // Then clear it
        $this->artisan('api:magic:cache --clear')
            ->assertExitCode(0);

        expect(base_path('bootstrap/cache/api-magic.json'))->not->toFileExist();
    });

    it('shows success message when clearing', function () {
        // First create cache
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        // Then clear it
        $this->artisan('api:magic:cache --clear')
            ->expectsOutputToContain('API documentation cache cleared')
            ->assertExitCode(0);
    });

    it('handles non-existent cache gracefully', function () {
        $this->artisan('api:magic:cache --clear')
            ->assertExitCode(0);
    });
});

describe('cache content', function () {
    it('includes generated_at timestamp', function () {
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData['generated_at'])->not->toBeEmpty();
        expect(\Carbon\Carbon::createFromFormat(\Carbon\Carbon::ISO8601, $cachedData['generated_at']))->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('includes endpoints array', function () {
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData['endpoints'])->toBeArray();
    });

    it('includes versions array', function () {
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData['versions'])->toBeArray();
        expect($cachedData['versions'])->toContain('1');
    });
});

describe('--force option', function () {
    it('overwrites existing cache', function () {
        // Create initial cache
        $this->artisan('api:magic:cache')
            ->assertExitCode(0);

        $firstContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $firstData = json_decode($firstContent, true);
        $firstTimestamp = $firstData['generated_at'];

        // Wait a bit to ensure timestamp difference
        usleep(100000); // 100ms

        // Force regenerate
        $this->artisan('api:magic:cache --force')
            ->assertExitCode(0);

        $secondContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $secondData = json_decode($secondContent, true);
        $secondTimestamp = $secondData['generated_at'];

        expect($secondTimestamp)->not->toBe($firstTimestamp);
    });
});
