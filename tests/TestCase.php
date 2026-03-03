<?php

namespace Arseno25\LaravelApiMagic\Tests;

use Arseno25\LaravelApiMagic\LaravelApiMagicServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        // Create Testbench cache directory before parent setup
        $this->ensureTestbenchCacheDirectory();

        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Arseno25\\LaravelApiMagic\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    private function ensureTestbenchCacheDirectory(): void
    {
        // Try multiple possible paths for Testbench cache directory
        $possiblePaths = [
            __DIR__.'/../vendor/orchestra/testbench-core/laravel/bootstrap/cache',
            dirname(__DIR__, 2).'/vendor/orchestra/testbench-core/laravel/bootstrap/cache',
        ];

        foreach ($possiblePaths as $path) {
            if (! is_dir($path)) {
                @mkdir($path, 0777, true);
            }
        }
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelApiMagicServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
