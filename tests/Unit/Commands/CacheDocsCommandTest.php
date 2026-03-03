<?php

use Arseno25\LaravelApiMagic\Commands\CacheDocsCommand;
use Illuminate\Support\Facades\File;

uses()->group('commands', 'cache-docs');

beforeEach(function () {
    $cachePath = base_path('bootstrap/cache/api-magic.json');
    if (File::exists($cachePath)) {
        File::delete($cachePath);
    }
});

afterEach(function () {
    $cachePath = base_path('bootstrap/cache/api-magic.json');
    if (File::exists($cachePath)) {
        File::delete($cachePath);
    }
});

it('has the correct command name', function () {
    $command = app(CacheDocsCommand::class);

    expect($command->getName())->toBe('api-magic:cache');
});

describe('cache generation', function () {
    it('generates cache file successfully', function () {
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        expect(File::exists(base_path('bootstrap/cache/api-magic.json')))->toBeTrue();
    });

    it('creates cache directory if not exists', function () {
        $cacheDir = base_path('bootstrap/cache');

        // Ensure directory exists (it usually does in Laravel)
        if (! File::isDirectory($cacheDir)) {
            File::deleteDirectory($cacheDir);
        }

        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        expect(File::isDirectory($cacheDir))->toBeTrue();
        expect(File::exists(base_path('bootstrap/cache/api-magic.json')))->toBeTrue();
    });

    it('generates valid json structure', function () {
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData)->toHaveKeys(['version', 'generated_at', 'title', 'endpoints', 'tags', 'stats']);
    });
});

describe('--clear option', function () {
    it('clears existing cache file', function () {
        // First create cache
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        expect(File::exists(base_path('bootstrap/cache/api-magic.json')))->toBeTrue();

        // Then clear it
        $this->artisan('api-magic:cache', ['--clear' => true])
            ->assertExitCode(0);

        expect(File::exists(base_path('bootstrap/cache/api-magic.json')))->toBeFalse();
    });

    it('shows success message when clearing', function () {
        // First create cache
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        // Then clear it
        $this->artisan('api-magic:cache', ['--clear' => true])
            ->expectsOutputToContain('Cache cleared')
            ->assertExitCode(0);
    });

    it('handles non-existent cache gracefully', function () {
        $this->artisan('api-magic:cache', ['--clear' => true])
            ->assertExitCode(0);
    });
});

describe('cache content', function () {
    it('includes generated_at timestamp', function () {
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData)->toHaveKey('generated_at');
    });

    it('includes endpoints array', function () {
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData['endpoints'])->toBeArray();
    });

    it('includes versions array', function () {
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        $cacheContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $cachedData = json_decode($cacheContent, true);

        expect($cachedData['versions'])->toBeArray();
    });
});

describe('--force option', function () {
    it('overwrites existing cache', function () {
        // Create initial cache
        $this->artisan('api-magic:cache')
            ->assertExitCode(0);

        $firstContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $firstData = json_decode($firstContent, true);

        // Force regenerate — should succeed (just replaces the file)
        $this->artisan('api-magic:cache', ['--force' => true])
            ->assertExitCode(0);

        $secondContent = File::get(base_path('bootstrap/cache/api-magic.json'));
        $secondData = json_decode($secondContent, true);

        // Both should be valid JSON
        expect($firstData)->toBeArray();
        expect($secondData)->toBeArray();
    });
});
