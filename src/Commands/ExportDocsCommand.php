<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Services\DocumentationSchemaBuilder;
use Arseno25\LaravelApiMagic\Services\OpenApiSchemaValidator;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Yaml\Yaml;

final class ExportDocsCommand extends Command
{
    protected $signature = 'api-magic:export
        {--path= : Custom output path (default: public/api-docs)}
        {--format=json : Output format (json or yaml)}
        {--strict : Fail if OpenAPI validation issues are detected}';

    protected $description = "Export OpenAPI documentation to a static file (JSON or YAML)";

    public function handle(
        DocumentationSchemaBuilder $schemaBuilder,
        OpenApiSchemaValidator $validator,
    ): int {
        $format = strtolower((string) $this->option("format"));

        if (!in_array($format, ["json", "yaml"])) {
            $this->error('Invalid format. Please use "json" or "yaml".');

            return self::FAILURE;
        }

        $basePath = $this->option("path");
        $basePath =
            is_string($basePath) && $basePath !== ""
                ? $basePath
                : public_path("api-docs");
        $extension = $format;
        // In case the user provided a full file name in the path argument
        if (
            str_ends_with($basePath, ".json") ||
            str_ends_with($basePath, ".yaml") ||
            str_ends_with($basePath, ".yml")
        ) {
            $outputPath = $basePath;
            $format = pathinfo($basePath, PATHINFO_EXTENSION);
            if ($format === "yml") {
                $format = "yaml";
            }
        } else {
            $outputPath = rtrim((string) $basePath, "/\\") . "." . $extension;
        }

        $this->info("Generating OpenAPI schema in {$format} format...");

        $request = Request::create(config("app.url", "/"));
        $schema = $schemaBuilder->buildOpenApiSchema($request);
        $issues = $validator->validate($schema);

        if ($issues !== []) {
            foreach ($issues as $issue) {
                $this->warn("OpenAPI validation: {$issue}");
            }

            if ($this->option("strict")) {
                $this->error(
                    "Export aborted because OpenAPI validation issues were detected.",
                );

                return self::FAILURE;
            }
        }

        $directory = dirname($outputPath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        if ($format === "yaml" && class_exists(Yaml::class)) {
            $output = Yaml::dump($schema, 10, 2);
        } elseif ($format === "yaml") {
            $this->warn(
                "symfony/yaml is not installed. Falling back to JSON format.",
            );
            $outputPath = preg_replace('/\.yaml$/', ".json", $outputPath);
            $output = json_encode(
                $schema,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            );
        } else {
            $output = json_encode(
                $schema,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            );
        }

        if (!is_string($outputPath)) {
            throw new \RuntimeException(
                "Unable to determine export output path.",
            );
        }

        if ($output === false) {
            throw new \RuntimeException("Unable to encode OpenAPI schema.");
        }

        File::put($outputPath, $output);

        $this->info("<fg=green>Documentation exported successfully!</>");
        $this->info("File saved to: {$outputPath}");

        return self::SUCCESS;
    }
}
