<?php

namespace Arseno25\LaravelApiMagic;

use Arseno25\LaravelApiMagic\Commands\CacheDocsCommand;
use Arseno25\LaravelApiMagic\Commands\ExportDocsCommand;
use Arseno25\LaravelApiMagic\Commands\GenerateApiCommand;
use Arseno25\LaravelApiMagic\Commands\GenerateGraphqlCommand;
use Arseno25\LaravelApiMagic\Commands\GenerateTypescriptCommand;
use Arseno25\LaravelApiMagic\Commands\InstallCommand;
use Arseno25\LaravelApiMagic\Commands\ReverseEngineerCommand;
use Arseno25\LaravelApiMagic\Commands\SnapshotSchemaCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelApiMagicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-api-magic')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoute('docs')
            ->hasMigration('create_laravel_api_magic_table')
            ->hasCommand(GenerateApiCommand::class)
            ->hasCommand(CacheDocsCommand::class)
            ->hasCommand(ExportDocsCommand::class)
            ->hasCommand(GenerateTypescriptCommand::class)
            ->hasCommand(ReverseEngineerCommand::class)
            ->hasCommand(SnapshotSchemaCommand::class)
            ->hasCommand(GenerateGraphqlCommand::class)
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->publishes(
            [
                __DIR__.'/../resources/stubs' => base_path(
                    'stubs/vendor/api-magic',
                ),
            ],
            'api-magic-stubs',
        );

        $this->publishes(
            [
                __DIR__.'/../resources/dist' => public_path(
                    'vendor/api-magic',
                ),
            ],
            'api-magic-assets',
        );
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        // Register middleware aliases
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware(
            'api.mock',
            \Arseno25\LaravelApiMagic\Http\Middleware\MockApiMiddleware::class,
        );
        $router->aliasMiddleware(
            'api.cache',
            \Arseno25\LaravelApiMagic\Http\Middleware\ApiCacheMiddleware::class,
        );
        $router->aliasMiddleware(
            'api.health',
            \Arseno25\LaravelApiMagic\Http\Middleware\ApiHealthMiddleware::class,
        );
    }
}
