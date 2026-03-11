<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Parsers\RequestAnalyzer;
use Arseno25\LaravelApiMagic\Parsers\RouteAnalyzer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class CacheDocsCommand extends Command
{
    protected $signature = 'api-magic:cache
        {--clear : Clear the cached documentation}
        {--force : Force regeneration even if cache exists}
        {--path= : Custom path for cache file (default: bootstrap/cache/api-magic.json)}';

    protected $description = 'Cache API documentation schema for better performance';

    public function handle(
        RouteAnalyzer $routeAnalyzer,
        RequestAnalyzer $requestAnalyzer,
    ): int {
        $cachePath =
            $this->option('path') ?:
            base_path('bootstrap/cache/api-magic.json');

        if ($this->option('clear')) {
            $this->clearCache($cachePath);

            return self::SUCCESS;
        }

        $this->info('Generating API documentation cache...');

        $schema = $this->buildSchema($routeAnalyzer, $requestAnalyzer);

        $this->saveCache($cachePath, $schema);

        $endpointCount = count($schema['endpoints'] ?? []);
        $this->info(
            "<fg=green>Documentation cached successfully!</> ({$endpointCount} endpoints)",
        );
        $this->info("Cache file: {$cachePath}");

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSchema(
        RouteAnalyzer $routeAnalyzer,
        RequestAnalyzer $requestAnalyzer,
    ): array {
        $routes = $routeAnalyzer->getApiRoutes();
        $endpoints = [];
        $tags = [];
        $endpointsByVersion = [];

        foreach ($routes as $route) {
            $endpoint = $routeAnalyzer->parseRoute($route, $requestAnalyzer);

            if (! $endpoint) {
                continue;
            }

            $pathKey = $endpoint['path'];
            $method = $endpoint['method'];
            $version = $endpoint['version'] ?? '1';

            if (! isset($endpoints[$pathKey])) {
                $endpoints[$pathKey] = [];
            }

            $endpoints[$pathKey][$method] = $endpoint;

            // Group by version
            if (! isset($endpointsByVersion[$version])) {
                $endpointsByVersion[$version] = [];
            }
            if (! isset($endpointsByVersion[$version][$pathKey])) {
                $endpointsByVersion[$version][$pathKey] = [];
            }
            $endpointsByVersion[$version][$pathKey][$method] = $endpoint;

            // Collect tags
            foreach ($endpoint['tags'] as $tag) {
                $tags[$tag] = true;
            }
        }

        return [
            'version' => '1.0.0',
            'generated_at' => now()->toIso8601String(),
            'title' => config('app.name', 'Laravel API').' Documentation',
            'endpoints' => $endpoints,
            'endpointsByVersion' => $endpointsByVersion,
            'versions' => array_keys($endpointsByVersion),
            'tags' => array_keys($tags),
            'stats' => [
                'total_endpoints' => count($endpoints),
                'total_paths' => count(array_keys($endpoints)),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    private function saveCache(string $path, array $schema): void
    {
        $directory = dirname($path);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $encodedSchema = json_encode(
            $schema,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );

        if ($encodedSchema === false) {
            throw new \RuntimeException('Unable to encode cached API schema.');
        }

        File::put($path, $encodedSchema);
    }

    private function clearCache(string $path): void
    {
        if (File::exists($path)) {
            File::delete($path);
            $this->info('<fg=green>Cache cleared:</> '.$path);
        } else {
            $this->warn('No cache file found at: '.$path);
        }
    }
}
