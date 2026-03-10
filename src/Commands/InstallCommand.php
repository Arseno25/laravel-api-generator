<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class InstallCommand extends Command
{
    protected $signature = 'api-magic:install
        {--force : Overwrite generated setup files when possible}
        {--stubs : Also publish the package stubs}
        {--without-assets : Skip publishing the bundled local docs assets}';

    protected $description = 'Install the package into an application by preparing API routes and publishing configuration';

    public function handle(): int
    {
        $routesCreated = $this->ensureApiRoutesFile();
        $bootstrapUpdated = $this->ensureBootstrapUsesApiRoutes();

        $this->publishTag('api-magic-config', 'Configuration published.');

        if ($this->option('stubs')) {
            $this->publishTag('api-magic-stubs', 'Stubs published.');
        }

        if (! $this->option('without-assets')) {
            $this->publishTag(
                'api-magic-assets',
                'Local docs assets published.',
            );
        }

        if ($routesCreated) {
            $this->info('Created routes/api.php.');
        } else {
            $this->line('routes/api.php already exists.');
        }

        if ($bootstrapUpdated) {
            $this->info('Updated bootstrap/app.php to load API routes.');
        } else {
            $this->line(
                'bootstrap/app.php already loads API routes or could not be updated automatically.',
            );
        }

        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Add your generated routes to routes/api.php.');
        $this->line(
            '2. Run `php artisan route:list --path=api` to verify the API routes are loaded.',
        );
        $this->line(
            '3. Docs UI assets are published to `public/vendor/api-magic` by default.',
        );

        return self::SUCCESS;
    }

    private function ensureApiRoutesFile(): bool
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (File::exists($apiRoutesPath) && ! $this->option('force')) {
            return false;
        }

        File::ensureDirectoryExists(dirname($apiRoutesPath));

        File::put(
            $apiRoutesPath,
            <<<'PHP'
            <?php

            use Illuminate\Support\Facades\Route;

            /*
            |--------------------------------------------------------------------------
            | API Routes
            |--------------------------------------------------------------------------
            |
            | Here is where you may register API routes for your application.
            |
            */
            PHP
            ,
        );

        return true;
    }

    private function ensureBootstrapUsesApiRoutes(): bool
    {
        $bootstrapPath = base_path('bootstrap/app.php');

        if (! File::exists($bootstrapPath)) {
            return false;
        }

        $content = File::get($bootstrapPath);
        $apiRoutesLine = "        api: __DIR__.'/../routes/api.php',";

        if (str_contains($content, 'routes/api.php')) {
            return false;
        }

        $updatedContent = $this->injectApiRoutesLine($content, $apiRoutesLine);

        if ($updatedContent === null) {
            return false;
        }

        File::put($bootstrapPath, $updatedContent);

        return true;
    }

    private function injectApiRoutesLine(
        string $content,
        string $apiRoutesLine,
    ): ?string {
        $webRoutesLine = "        web: __DIR__.'/../routes/web.php',";
        if (str_contains($content, $webRoutesLine)) {
            return str_replace(
                $webRoutesLine,
                $webRoutesLine."\n".$apiRoutesLine,
                $content,
            );
        }

        $commandsRoutesLine =
            "        commands: __DIR__.'/../routes/console.php',";
        if (str_contains($content, $commandsRoutesLine)) {
            return str_replace(
                $commandsRoutesLine,
                $apiRoutesLine."\n".$commandsRoutesLine,
                $content,
            );
        }

        return null;
    }

    private function publishTag(string $tag, string $message): void
    {
        $this->call('vendor:publish', [
            '--tag' => $tag,
            '--force' => $this->option('force'),
        ]);

        $this->line($message);
    }
}
