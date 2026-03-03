<?php

namespace Arseno25\LaravelApiMagic;

use Arseno25\LaravelApiMagic\Commands\CacheDocsCommand;
use Arseno25\LaravelApiMagic\Commands\GenerateApiCommand;
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
            ->hasCommand(CacheDocsCommand::class);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->publishes([
            __DIR__.'/../resources/stubs' => base_path('stubs/vendor/api-magic'),
        ], 'api-magic-stubs');
    }
}
