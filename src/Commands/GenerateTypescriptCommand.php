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
        {--namespace=ApiTypes : TypeScript namespace}';

    protected $description = 'Generate TypeScript interfaces from your API schema';

    public function handle(DocsController $docsController, TypescriptGenerator $generator): int
    {
        $this->info('🔧 Generating TypeScript interfaces...');

        $request = Request::create(config('app.url', '/'));
        $schema = $docsController->generateSchemaPublic($request);

        $namespace = (string) $this->option('namespace');
        $output = $generator->generate($schema, $namespace);

        $outputPath = base_path((string) $this->option('output'));
        $directory = dirname($outputPath);

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($outputPath, $output);

        $interfaceCount = substr_count($output, 'interface ');

        $this->info("<fg=green>✅ Generated {$interfaceCount} TypeScript interfaces!</>");
        $this->info("   File saved to: {$outputPath}");

        return self::SUCCESS;
    }
}
