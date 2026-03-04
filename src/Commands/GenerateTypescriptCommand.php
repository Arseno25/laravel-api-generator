<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Generators\TypescriptGenerator;
use Arseno25\LaravelApiMagic\Http\Controllers\DocsController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

final class GenerateTypescriptCommand extends Command
{
    protected $signature = 'api-magic:ts
        {--output=resources/js/api-types.d.ts : Output file path}
        {--namespace=ApiTypes : TypeScript namespace}
        {--sdk : Generate a full API client SDK instead of just interfaces}';

    protected $description = 'Generate TypeScript interfaces or a full API client SDK from your API schema';

    public function handle(DocsController $docsController, TypescriptGenerator $generator): int
    {
        $isSdk = $this->option('sdk');

        $this->info($isSdk ? '🔧 Generating TypeScript API Client SDK...' : '🔧 Generating TypeScript interfaces...');

        $request = Request::create(config('app.url', '/'));
        $schema = $docsController->generateSchemaPublic($request);

        if ($isSdk) {
            $baseUrl = $schema['baseUrl'] ?? config('app.url', 'http://localhost');
            $output = $generator->generateSdk($schema, $baseUrl);
            $defaultOutput = 'resources/js/api-client.ts';
        } else {
            $namespace = (string) $this->option('namespace');
            $output = $generator->generate($schema, $namespace);
            $defaultOutput = 'resources/js/api-types.d.ts';
        }

        $outputPath = base_path($this->option('output') !== 'resources/js/api-types.d.ts' || ! $isSdk
            ? (string) $this->option('output')
            : $defaultOutput);
        $directory = dirname($outputPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        try {
            File::put($outputPath, $output);
        } catch (\Throwable $e) {
            $this->error("❌ Failed to write file: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($isSdk) {
            $methodCount = substr_count($output, 'async ') - 1; // subtract the private request method
            $this->info("<fg=green>✅ Generated API Client with {$methodCount} typed methods!</>");
        } else {
            $interfaceCount = substr_count($output, 'interface ');
            $this->info("<fg=green>✅ Generated {$interfaceCount} TypeScript interfaces!</>");
        }

        $this->info("   File saved to: {$outputPath}");

        return self::SUCCESS;
    }
}

