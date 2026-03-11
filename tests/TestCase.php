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
            fn (
                string $modelName,
            ) => 'Arseno25\\LaravelApiMagic\\Database\\Factories\\'.
                class_basename($modelName).
                'Factory',
        );
    }

    private function ensureTestbenchCacheDirectory(): void
    {
        // Try multiple possible paths for Testbench cache directory
        $possiblePaths = [
            __DIR__.
            '/../vendor/orchestra/testbench-core/laravel/bootstrap/cache',
            dirname(__DIR__, 2).
            '/vendor/orchestra/testbench-core/laravel/bootstrap/cache',
        ];

        foreach ($possiblePaths as $path) {
            if (! is_dir($path)) {
                @mkdir($path, 0777, true);
            }
        }
    }

    protected function getPackageProviders($app)
    {
        return [LaravelApiMagicServiceProvider::class];
    }

    public function getEnvironmentSetUp($app)
    {
        $databaseConnection = $_SERVER['DB_CONNECTION'] ?? 'sqlite';

        config()->set('database.default', $databaseConnection);

        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'url' => null,
            'database' => $_SERVER['DB_DATABASE'] ?? ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        config()->set('database.connections.mysql', [
            'driver' => 'mysql',
            'url' => null,
            'host' => $_SERVER['DB_HOST'] ?? '127.0.0.1',
            'port' => $_SERVER['DB_PORT'] ?? '3306',
            'database' => $_SERVER['DB_DATABASE'] ?? 'laravel_api_magic',
            'username' => $_SERVER['DB_USERNAME'] ?? 'root',
            'password' => $_SERVER['DB_PASSWORD'] ?? '',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
        ]);

        config()->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'url' => null,
            'host' => $_SERVER['DB_HOST'] ?? '127.0.0.1',
            'port' => $_SERVER['DB_PORT'] ?? '5432',
            'database' => $_SERVER['DB_DATABASE'] ?? 'laravel_api_magic',
            'username' => $_SERVER['DB_USERNAME'] ?? 'postgres',
            'password' => $_SERVER['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ]);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
