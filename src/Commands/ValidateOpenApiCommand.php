<?php

namespace Arseno25\LaravelApiMagic\Commands;

use Arseno25\LaravelApiMagic\Services\DocumentationSchemaBuilder;
use Arseno25\LaravelApiMagic\Services\OpenApiSchemaValidator;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

final class ValidateOpenApiCommand extends Command
{
    protected $signature = 'api-magic:validate';

    protected $description = 'Validate the generated OpenAPI schema for interoperability issues';

    public function handle(
        DocumentationSchemaBuilder $schemaBuilder,
        OpenApiSchemaValidator $validator,
    ): int {
        $this->info('Validating OpenAPI schema...');

        $schema = $schemaBuilder->buildOpenApiSchema(
            Request::create(config('app.url', '/')),
        );

        $issues = $validator->validate($schema);

        if ($issues === []) {
            $this->info('OpenAPI schema is valid.');

            return self::SUCCESS;
        }

        $this->error('OpenAPI schema validation failed:');

        foreach ($issues as $issue) {
            $this->line(" - {$issue}");
        }

        return self::FAILURE;
    }
}
